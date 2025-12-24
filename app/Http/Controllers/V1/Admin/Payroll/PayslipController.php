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
     * Preview payslip PDF in browser.
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

        // Create temporary directory for PDFs
        $tempDir = storage_path('app/temp/payslips_'.time());
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $files = [];

        try {
            // Generate all payslips
            foreach ($lines as $line) {
                $data = [
                    'payrollRunLine' => $line,
                    'payrollRun' => $payrollRun,
                    'employee' => $line->employee,
                    'company' => $line->employee->company,
                    'periodName' => $payrollRun->period_name,
                    'generatedAt' => now()->format('d.m.Y H:i'),
                ];

                $pdf = Pdf::loadView('app.pdf.payroll.payslip-mk', $data);
                $pdf->setPaper('A4', 'portrait');

                $filename = sprintf(
                    'payslip_%s_%s_%s.pdf',
                    $line->employee->employee_number,
                    $payrollRun->period_year,
                    str_pad($payrollRun->period_month, 2, '0', STR_PAD_LEFT)
                );

                $filepath = $tempDir.'/'.$filename;
                $pdf->save($filepath);
                $files[] = $filepath;
            }

            // Create ZIP archive
            $zipFilename = sprintf(
                'payslips_%s_%s.zip',
                $payrollRun->period_year,
                str_pad($payrollRun->period_month, 2, '0', STR_PAD_LEFT)
            );

            $zipPath = storage_path('app/temp/'.$zipFilename);

            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                foreach ($files as $file) {
                    $zip->addFile($file, basename($file));
                }
                $zip->close();
            }

            // Clean up individual PDFs
            foreach ($files as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
            rmdir($tempDir);

            // Download and delete ZIP
            return response()->download($zipPath, $zipFilename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            // Clean up on error
            foreach ($files as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
            if (file_exists($tempDir)) {
                rmdir($tempDir);
            }

            return response()->json([
                'error' => 'bulk_download_failed',
                'message' => 'Failed to generate payslips: '.$e->getMessage(),
            ], 500);
        }
    }
}

// LLM-CHECKPOINT
