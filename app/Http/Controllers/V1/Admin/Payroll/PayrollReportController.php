<?php

namespace App\Http\Controllers\V1\Admin\Payroll;

use App\Http\Controllers\Controller;
use App\Models\PayrollRun;
use App\Models\PayrollRunLine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Mk\Payroll\Services\UjpEfilingService;

class PayrollReportController extends Controller
{
    /**
     * Get tax summary report for a specific period or year.
     */
    public function taxSummary(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PayrollRun::class);

        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
        ]);

        $companyId = $request->header('company');
        $year = $validated['year'];
        $month = $validated['month'] ?? null;

        $query = PayrollRun::forCompany($companyId)
            ->where('period_year', $year)
            ->whereIn('status', [
                PayrollRun::STATUS_APPROVED,
                PayrollRun::STATUS_POSTED,
                PayrollRun::STATUS_PAID,
            ]);

        if ($month) {
            $query->where('period_month', $month);
        }

        $runs = $query->with('lines')->get();

        // Calculate totals
        $summary = [
            'period' => $month ? sprintf('%s %d', date('F', mktime(0, 0, 0, $month, 1)), $year) : "Year $year",
            'year' => $year,
            'month' => $month,
            'total_gross' => 0,
            'total_net' => 0,
            'total_income_tax' => 0,
            'total_pension_employee' => 0,
            'total_pension_employer' => 0,
            'total_health_employee' => 0,
            'total_health_employer' => 0,
            'total_unemployment' => 0,
            'total_additional' => 0,
            'total_employee_contributions' => 0,
            'total_employer_contributions' => 0,
            'total_employer_cost' => 0,
            'payroll_run_count' => $runs->count(),
            'employee_count' => 0,
        ];

        $employeeIds = [];

        foreach ($runs as $run) {
            $summary['total_gross'] += $run->total_gross;
            $summary['total_net'] += $run->total_net;

            foreach ($run->lines as $line) {
                $employeeIds[$line->employee_id] = true;

                $summary['total_income_tax'] += $line->income_tax_amount;
                $summary['total_pension_employee'] += $line->pension_contribution_employee;
                $summary['total_pension_employer'] += $line->pension_contribution_employer;
                $summary['total_health_employee'] += $line->health_contribution_employee;
                $summary['total_health_employer'] += $line->health_contribution_employer;
                $summary['total_unemployment'] += $line->unemployment_contribution;
                $summary['total_additional'] += $line->additional_contribution;
            }
        }

        $summary['employee_count'] = count($employeeIds);
        $summary['total_employee_contributions'] = $summary['total_pension_employee'] +
            $summary['total_health_employee'] +
            $summary['total_unemployment'] +
            $summary['total_additional'];

        $summary['total_employer_contributions'] = $summary['total_pension_employer'] +
            $summary['total_health_employer'];

        $summary['total_employer_cost'] = $summary['total_gross'] +
            $summary['total_employer_contributions'];

        return response()->json([
            'data' => $summary,
        ]);
    }

    /**
     * Get payroll statistics for dashboard.
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PayrollRun::class);

        $companyId = $request->header('company');
        $currentYear = now()->year;
        $currentMonth = now()->month;

        // Current month stats
        $currentMonthRun = PayrollRun::forCompany($companyId)
            ->forPeriod($currentYear, $currentMonth)
            ->first();

        // Year to date stats
        $ytdRuns = PayrollRun::forCompany($companyId)
            ->where('period_year', $currentYear)
            ->whereIn('status', [
                PayrollRun::STATUS_APPROVED,
                PayrollRun::STATUS_POSTED,
                PayrollRun::STATUS_PAID,
            ])
            ->get();

        $ytdGross = $ytdRuns->sum('total_gross');
        $ytdNet = $ytdRuns->sum('total_net');
        $ytdEmployerTax = $ytdRuns->sum('total_employer_tax');
        $ytdEmployeeTax = $ytdRuns->sum('total_employee_tax');

        // Active employees count
        $activeEmployeesCount = \App\Models\PayrollEmployee::forCompany($companyId)
            ->active()
            ->count();

        // Pending payroll runs
        $pendingRunsCount = PayrollRun::forCompany($companyId)
            ->whereIn('status', [PayrollRun::STATUS_DRAFT, PayrollRun::STATUS_CALCULATED])
            ->count();

        // Recent payroll runs for dashboard table
        $recentRuns = PayrollRun::forCompany($companyId)
            ->with(['creator'])
            ->orderBy('period_start', 'desc')
            ->limit(5)
            ->get();

        // Return data in the format Vue expects
        $statistics = [
            'active_employees' => $activeEmployeesCount,
            'current_month_gross' => $currentMonthRun ? $currentMonthRun->total_gross : 0,
            'current_month_net' => $currentMonthRun ? $currentMonthRun->total_net : 0,
            'pending_runs' => $pendingRunsCount,
            'current_month' => [
                'status' => $currentMonthRun ? $currentMonthRun->status : 'not_created',
                'total_gross' => $currentMonthRun ? $currentMonthRun->total_gross : 0,
                'total_net' => $currentMonthRun ? $currentMonthRun->total_net : 0,
                'employee_count' => $currentMonthRun ? $currentMonthRun->lines()->count() : 0,
            ],
            'year_to_date' => [
                'year' => $currentYear,
                'total_gross' => $ytdGross,
                'total_net' => $ytdNet,
                'total_employer_tax' => $ytdEmployerTax,
                'total_employee_tax' => $ytdEmployeeTax,
                'total_employer_cost' => $ytdGross + $ytdEmployerTax,
                'payroll_runs_count' => $ytdRuns->count(),
            ],
        ];

        return response()->json([
            'data' => $statistics,
            'recent_runs' => $recentRuns,
        ]);
    }

    /**
     * Get employee payroll history.
     */
    public function employeeHistory(Request $request, int $employeeId): JsonResponse
    {
        $this->authorize('viewAny', PayrollRun::class);

        $companyId = $request->header('company');

        // Verify employee belongs to company
        $employee = \App\Models\PayrollEmployee::forCompany($companyId)
            ->findOrFail($employeeId);

        $validated = $request->validate([
            'year' => 'nullable|integer|min:2020|max:2100',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $limit = $validated['limit'] ?? 12;

        $query = PayrollRunLine::where('employee_id', $employeeId)
            ->whereHas('payrollRun', function ($q) use ($companyId) {
                $q->forCompany($companyId);
            })
            ->with(['payrollRun']);

        if (isset($validated['year'])) {
            $query->whereHas('payrollRun', function ($q) use ($validated) {
                $q->where('period_year', $validated['year']);
            });
        }

        $lines = $query->orderByDesc('id')
            ->limit($limit)
            ->get();

        // Calculate totals
        $totals = [
            'gross_salary' => $lines->sum('gross_salary'),
            'net_salary' => $lines->sum('net_salary'),
            'income_tax' => $lines->sum('income_tax_amount'),
            'total_employee_contributions' => $lines->sum('total_employee_contributions'),
            'total_employer_contributions' => $lines->sum('total_employer_contributions'),
        ];

        return response()->json([
            'data' => [
                'employee' => $employee,
                'payroll_lines' => $lines,
                'totals' => $totals,
            ],
        ]);
    }

    /**
     * Get monthly comparison report.
     */
    public function monthlyComparison(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PayrollRun::class);

        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
        ]);

        $companyId = $request->header('company');
        $year = $validated['year'];

        $runs = PayrollRun::forCompany($companyId)
            ->where('period_year', $year)
            ->whereIn('status', [
                PayrollRun::STATUS_APPROVED,
                PayrollRun::STATUS_POSTED,
                PayrollRun::STATUS_PAID,
            ])
            ->orderBy('period_month')
            ->get();

        $monthlyData = [];

        for ($month = 1; $month <= 12; $month++) {
            $run = $runs->firstWhere('period_month', $month);

            $monthlyData[] = [
                'month' => $month,
                'month_name' => date('F', mktime(0, 0, 0, $month, 1)),
                'has_payroll' => $run !== null,
                'total_gross' => $run ? $run->total_gross : 0,
                'total_net' => $run ? $run->total_net : 0,
                'total_employer_tax' => $run ? $run->total_employer_tax : 0,
                'total_employee_tax' => $run ? $run->total_employee_tax : 0,
                'employee_count' => $run ? $run->lines()->count() : 0,
                'status' => $run ? $run->status : null,
            ];
        }

        return response()->json([
            'data' => [
                'year' => $year,
                'months' => $monthlyData,
                'yearly_totals' => [
                    'total_gross' => $runs->sum('total_gross'),
                    'total_net' => $runs->sum('total_net'),
                    'total_employer_tax' => $runs->sum('total_employer_tax'),
                    'total_employee_tax' => $runs->sum('total_employee_tax'),
                    'total_employer_cost' => $runs->sum('total_gross') + $runs->sum('total_employer_tax'),
                ],
            ],
        ]);
    }

    /**
     * Export tax summary as CSV for UJP filing.
     */
    public function exportTaxSummary(Request $request)
    {
        $this->authorize('viewAny', PayrollRun::class);

        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
        ]);

        $companyId = $request->header('company');
        $year = $validated['year'];
        $month = $validated['month'] ?? null;

        $query = PayrollRun::forCompany($companyId)
            ->where('period_year', $year)
            ->whereIn('status', [
                PayrollRun::STATUS_APPROVED,
                PayrollRun::STATUS_POSTED,
                PayrollRun::STATUS_PAID,
            ]);

        if ($month) {
            $query->where('period_month', $month);
        }

        $runs = $query->with(['lines.employee'])->get();

        // Generate CSV
        $filename = sprintf('payroll_tax_summary_%s.csv', $month ? "{$year}_{$month}" : $year);
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($runs) {
            $file = fopen('php://output', 'w');

            // CSV Headers
            fputcsv($file, [
                'Employee Number',
                'EMBG',
                'Full Name',
                'Period',
                'Gross Salary',
                'Pension Employee',
                'Pension Employer',
                'Health Employee',
                'Health Employer',
                'Unemployment',
                'Additional',
                'Income Tax',
                'Net Salary',
            ]);

            foreach ($runs as $run) {
                foreach ($run->lines as $line) {
                    fputcsv($file, [
                        $line->employee->employee_number,
                        $line->employee->embg,
                        $line->employee->full_name,
                        $run->period_name,
                        number_format($line->gross_salary / 100, 2, '.', ''),
                        number_format($line->pension_contribution_employee / 100, 2, '.', ''),
                        number_format($line->pension_contribution_employer / 100, 2, '.', ''),
                        number_format($line->health_contribution_employee / 100, 2, '.', ''),
                        number_format($line->health_contribution_employer / 100, 2, '.', ''),
                        number_format($line->unemployment_contribution / 100, 2, '.', ''),
                        number_format($line->additional_contribution / 100, 2, '.', ''),
                        number_format($line->income_tax_amount / 100, 2, '.', ''),
                        number_format($line->net_salary / 100, 2, '.', ''),
                    ]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Download UJP MPIN XML for monthly payroll tax filing.
     */
    public function downloadMpinXml(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorize('viewAny', PayrollRun::class);

        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $companyId = $request->header('company');
        $year = $validated['year'];
        $month = $validated['month'];

        // Get the payroll run for the period
        $payrollRun = PayrollRun::forCompany($companyId)
            ->forPeriod($year, $month)
            ->whereIn('status', [
                PayrollRun::STATUS_APPROVED,
                PayrollRun::STATUS_POSTED,
                PayrollRun::STATUS_PAID,
            ])
            ->first();

        if (! $payrollRun) {
            return response()->json([
                'error' => 'No approved payroll run found for the specified period.',
            ], 404);
        }

        // Get company
        $company = \App\Models\Company::find($companyId);

        // Generate MPIN XML
        $ujpService = new UjpEfilingService();
        $xml = $ujpService->generateMpinXml($payrollRun, $company);

        // Validate XML
        if (! $ujpService->validateMpinXml($xml)) {
            return response()->json([
                'error' => 'Generated XML failed validation.',
            ], 500);
        }

        // Generate filename
        $filename = sprintf('MPIN_%s_%s_%02d.xml', $company->unique_hash, $year, $month);

        // Return XML as download
        return response($xml, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    /**
     * Download UJP DDV-04 XML for annual employee income report.
     */
    public function downloadDdv04Xml(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorize('viewAny', PayrollRun::class);

        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
        ]);

        $companyId = $request->header('company');
        $year = $validated['year'];

        // Get all payroll runs for the year
        $payrollRuns = PayrollRun::forCompany($companyId)
            ->where('period_year', $year)
            ->whereIn('status', [
                PayrollRun::STATUS_APPROVED,
                PayrollRun::STATUS_POSTED,
                PayrollRun::STATUS_PAID,
            ])
            ->with(['lines.employee'])
            ->get();

        if ($payrollRuns->isEmpty()) {
            return response()->json([
                'error' => 'No approved payroll runs found for the specified year.',
            ], 404);
        }

        // Get company
        $company = \App\Models\Company::find($companyId);

        // Generate DDV-04 XML
        $ujpService = new UjpEfilingService();
        $xml = $ujpService->generateDdv04Xml($year, $company, $payrollRuns);

        // Validate XML
        if (! $ujpService->validateDdv04Xml($xml)) {
            return response()->json([
                'error' => 'Generated XML failed validation.',
            ], 500);
        }

        // Generate filename
        $filename = sprintf('DDV04_%s_%s.xml', $company->unique_hash, $year);

        // Return XML as download
        return response($xml, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }
}

// LLM-CHECKPOINT
