<?php

namespace App\Http\Controllers\V1\Admin\Payroll;

use App\Http\Controllers\Controller;
use App\Models\PayrollRunLine;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PayslipController extends Controller
{
    /**
     * Generate and download payslip PDF for a specific employee payroll line.
     */
    public function download(Request $request, PayrollRunLine $payrollRunLine)
    {
        $this->authorize('view', $payrollRunLine);

        // Load required relationships
        $payrollRunLine->load([
            'employee.currency',
            'employee.company',
            'payrollRun',
        ]);

        $payrollRun = $payrollRunLine->payrollRun;
        $employee = $payrollRunLine->employee;
        $company = $employee->company;

        // Prepare data for the view
        $data = [
            'payrollRunLine' => $payrollRunLine,
            'payrollRun' => $payrollRun,
            'employee' => $employee,
            'company' => $company,
            'periodName' => $payrollRun->period_name,
            'generatedAt' => now()->format('d.m.Y H:i'),
        ];

        // Generate PDF using Macedonian template (default for Facturino)
        $pdf = Pdf::loadView('app.pdf.payroll.payslip-mk', $data);

        // Set paper size and orientation
        $pdf->setPaper('A4', 'portrait');

        // Generate filename
        $filename = sprintf(
            'payslip_%s_%s_%s.pdf',
            $employee->employee_number,
            $payrollRun->period_year,
            str_pad($payrollRun->period_month, 2, '0', STR_PAD_LEFT)
        );

        // Download the PDF
        return $pdf->download($filename);
    }

    /**
     * Get payslip data as JSON for Vue display.
     */
    public function preview(Request $request, PayrollRunLine $payrollRunLine)
    {
        $this->authorize('view', $payrollRunLine);

        // Load required relationships
        $payrollRunLine->load([
            'employee.currency',
            'employee.company',
            'payrollRun',
        ]);

        return response()->json([
            'data' => $payrollRunLine,
        ]);
    }

    /**
     * Preview payslip PDF in browser.
     */
    public function previewPdf(Request $request, PayrollRunLine $payrollRunLine)
    {
        $this->authorize('view', $payrollRunLine);

        // Load required relationships
        $payrollRunLine->load([
            'employee.currency',
            'employee.company',
            'payrollRun',
        ]);

        $payrollRun = $payrollRunLine->payrollRun;
        $employee = $payrollRunLine->employee;
        $company = $employee->company;

        // Prepare data for the view
        $data = [
            'payrollRunLine' => $payrollRunLine,
            'payrollRun' => $payrollRun,
            'employee' => $employee,
            'company' => $company,
            'periodName' => $payrollRun->period_name,
            'generatedAt' => now()->format('d.m.Y H:i'),
        ];

        // Generate PDF using Macedonian template (default for Facturino)
        $pdf = Pdf::loadView('app.pdf.payroll.payslip-mk', $data);

        // Set paper size and orientation
        $pdf->setPaper('A4', 'portrait');

        // Stream to browser
        return $pdf->stream();
    }

    /**
     * Bulk download payslips for a payroll run (ZIP).
     */
    public function bulkDownload(Request $request, int $payrollRunId)
    {
        $payrollRun = \App\Models\PayrollRun::findOrFail($payrollRunId);

        $this->authorize('view', $payrollRun);

        $lines = $payrollRun->lines()
            ->with(['employee.currency', 'employee.company'])
            ->get();

        if ($lines->isEmpty()) {
            return response()->json([
                'error' => 'no_payslips',
                'message' => 'No payslips found for this payroll run.',
            ], 404);
        }

        // Ensure temp directory exists
        $tempBase = storage_path('app/temp');
        if (!file_exists($tempBase)) {
            mkdir($tempBase, 0755, true);
        }

        // Create temporary directory for PDFs
        $tempDir = $tempBase.'/payslips_'.time().'_'.uniqid();
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $files = [];
        $errors = [];

        try {
            // Generate all payslips
            foreach ($lines as $line) {
                try {
                    // Skip employees without required data
                    if (!$line->employee) {
                        $errors[] = "Line {$line->id}: No employee data";
                        continue;
                    }

                    $data = [
                        'payrollRunLine' => $line,
                        'payrollRun' => $payrollRun,
                        'employee' => $line->employee,
                        'company' => $line->employee->company ?? $payrollRun->company,
                        'periodName' => $payrollRun->period_name ?? "{$payrollRun->period_month}/{$payrollRun->period_year}",
                        'generatedAt' => now()->format('d.m.Y H:i'),
                    ];

                    $pdf = Pdf::loadView('app.pdf.payroll.payslip-mk', $data);
                    $pdf->setPaper('A4', 'portrait');

                    $employeeNumber = $line->employee->employee_number ?? $line->employee->id;
                    $filename = sprintf(
                        'payslip_%s_%s_%s.pdf',
                        $employeeNumber,
                        $payrollRun->period_year,
                        str_pad($payrollRun->period_month, 2, '0', STR_PAD_LEFT)
                    );

                    $filepath = $tempDir.'/'.$filename;
                    $pdf->save($filepath);
                    $files[] = $filepath;
                } catch (\Exception $pdfError) {
                    $errors[] = "Employee {$line->employee_id}: ".$pdfError->getMessage();
                    \Log::error('Payslip PDF generation failed', [
                        'employee_id' => $line->employee_id,
                        'error' => $pdfError->getMessage(),
                    ]);
                }
            }

            if (empty($files)) {
                throw new \Exception('No payslips could be generated. Errors: '.implode('; ', $errors));
            }

            // Create ZIP archive
            $zipFilename = sprintf(
                'payslips_%s_%s.zip',
                $payrollRun->period_year,
                str_pad($payrollRun->period_month, 2, '0', STR_PAD_LEFT)
            );

            $zipPath = $tempBase.'/'.$zipFilename;

            $zip = new \ZipArchive();
            $zipResult = $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

            if ($zipResult !== true) {
                throw new \Exception("Failed to create ZIP archive. Error code: {$zipResult}");
            }

            foreach ($files as $file) {
                if (file_exists($file)) {
                    $zip->addFile($file, basename($file));
                }
            }
            $zip->close();

            // Verify ZIP was created
            if (!file_exists($zipPath) || filesize($zipPath) === 0) {
                throw new \Exception('ZIP file was not created or is empty');
            }

            // Clean up individual PDFs
            foreach ($files as $file) {
                if (file_exists($file)) {
                    @unlink($file);
                }
            }
            @rmdir($tempDir);

            // Return download response with proper headers
            return response()->download($zipPath, $zipFilename, [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => 'attachment; filename="'.$zipFilename.'"',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            // Clean up on error
            foreach ($files as $file) {
                if (file_exists($file)) {
                    @unlink($file);
                }
            }
            if (file_exists($tempDir)) {
                @rmdir($tempDir);
            }

            \Log::error('Bulk payslip download failed', [
                'payroll_run_id' => $payrollRunId,
                'error' => $e->getMessage(),
                'errors' => $errors,
            ]);

            return response()->json([
                'error' => 'bulk_download_failed',
                'message' => 'Failed to generate payslips: '.$e->getMessage(),
            ], 500);
        }
    }
}

// LLM-CHECKPOINT
