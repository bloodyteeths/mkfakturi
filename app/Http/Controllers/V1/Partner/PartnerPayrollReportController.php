<?php

namespace App\Http\Controllers\V1\Partner;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Partner;
use App\Models\PayrollRun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Mk\Payroll\Services\UjpEfilingService;

/**
 * Partner Payroll Report Controller
 *
 * Provides partner access to payroll reports, tax summaries, and
 * MPIN/DDV-04 XML downloads for client companies.
 */
class PartnerPayrollReportController extends Controller
{
    /**
     * Get tax summary report for a specific period or year.
     */
    public function taxSummary(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
        ]);

        $year = $validated['year'];
        $month = $validated['month'] ?? null;

        $query = PayrollRun::forCompany($company)
            ->where('period_year', $year)
            ->whereIn('status', [
                PayrollRun::STATUS_CALCULATED,
                PayrollRun::STATUS_APPROVED,
                PayrollRun::STATUS_POSTED,
                PayrollRun::STATUS_PAID,
            ]);

        if ($month) {
            $query->where('period_month', $month);
        }

        $runs = $query->with('lines')->get();

        $summary = [
            'period' => $month ? sprintf('%s %d', $this->mkMonthName($month), $year) : "Година $year",
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

        return response()->json(['data' => $summary]);
    }

    /**
     * Get payroll statistics for dashboard.
     */
    public function statistics(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $currentYear = now()->year;
        $currentMonth = now()->month;

        $currentMonthRun = PayrollRun::forCompany($company)
            ->forPeriod($currentYear, $currentMonth)
            ->whereIn('status', [
                PayrollRun::STATUS_CALCULATED,
                PayrollRun::STATUS_APPROVED,
                PayrollRun::STATUS_POSTED,
                PayrollRun::STATUS_PAID,
            ])
            ->withCount('lines')
            ->first();

        $ytdRuns = PayrollRun::forCompany($company)
            ->where('period_year', $currentYear)
            ->whereIn('status', [
                PayrollRun::STATUS_CALCULATED,
                PayrollRun::STATUS_APPROVED,
                PayrollRun::STATUS_POSTED,
                PayrollRun::STATUS_PAID,
            ])
            ->get();

        $activeEmployeesCount = \App\Models\PayrollEmployee::forCompany($company)
            ->active()
            ->count();

        $statistics = [
            'active_employees' => $activeEmployeesCount,
            'current_month' => [
                'status' => $currentMonthRun ? $currentMonthRun->status : 'not_created',
                'total_gross' => $currentMonthRun ? $currentMonthRun->total_gross : 0,
                'total_net' => $currentMonthRun ? $currentMonthRun->total_net : 0,
                'employee_count' => $currentMonthRun ? $currentMonthRun->lines_count : 0,
            ],
            'year_to_date' => [
                'year' => $currentYear,
                'total_gross' => $ytdRuns->sum('total_gross'),
                'total_net' => $ytdRuns->sum('total_net'),
                'total_employer_tax' => $ytdRuns->sum('total_employer_tax'),
                'total_employee_tax' => $ytdRuns->sum('total_employee_tax'),
                'payroll_runs_count' => $ytdRuns->count(),
            ],
        ];

        return response()->json(['data' => $statistics]);
    }

    /**
     * Get monthly comparison report.
     */
    public function monthlyComparison(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
        ]);

        $year = $validated['year'];

        $runs = PayrollRun::forCompany($company)
            ->where('period_year', $year)
            ->whereIn('status', [
                PayrollRun::STATUS_CALCULATED,
                PayrollRun::STATUS_APPROVED,
                PayrollRun::STATUS_POSTED,
                PayrollRun::STATUS_PAID,
            ])
            ->withCount('lines')
            ->orderBy('period_month')
            ->get();

        $monthlyData = [];
        for ($month = 1; $month <= 12; $month++) {
            $run = $runs->firstWhere('period_month', $month);
            $monthlyData[] = [
                'month' => $month,
                'month_name' => $this->mkMonthName($month),
                'has_payroll' => $run !== null,
                'total_gross' => $run ? $run->total_gross : 0,
                'total_net' => $run ? $run->total_net : 0,
                'total_employer_tax' => $run ? $run->total_employer_tax : 0,
                'total_employee_tax' => $run ? $run->total_employee_tax : 0,
                'employee_count' => $run ? $run->lines_count : 0,
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
     * Get per-employee breakdown for a period.
     */
    public function employeeBreakdown(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
        ]);

        $year = $validated['year'];
        $month = $validated['month'] ?? null;

        $query = PayrollRun::forCompany($company)
            ->where('period_year', $year)
            ->whereIn('status', [
                PayrollRun::STATUS_CALCULATED,
                PayrollRun::STATUS_APPROVED,
                PayrollRun::STATUS_POSTED,
                PayrollRun::STATUS_PAID,
            ]);

        if ($month) {
            $query->where('period_month', $month);
        }

        $runs = $query->with(['lines.employee'])->get();

        // Aggregate per employee
        $employees = [];
        foreach ($runs as $run) {
            foreach ($run->lines as $line) {
                $empId = $line->employee_id;
                if (!isset($employees[$empId])) {
                    $employees[$empId] = [
                        'employee_id' => $empId,
                        'full_name' => $line->employee->full_name ?? '',
                        'embg' => $line->employee->embg ?? '',
                        'gross_salary' => 0,
                        'net_salary' => 0,
                        'income_tax' => 0,
                        'total_employee_contributions' => 0,
                        'total_employer_contributions' => 0,
                        'employer_cost' => 0,
                    ];
                }
                $employees[$empId]['gross_salary'] += $line->gross_salary;
                $employees[$empId]['net_salary'] += $line->net_salary;
                $employees[$empId]['income_tax'] += $line->income_tax_amount;
                $empContribs = ($line->pension_contribution_employee ?? 0) +
                    ($line->health_contribution_employee ?? 0) +
                    ($line->unemployment_contribution ?? 0) +
                    ($line->additional_contribution ?? 0);
                $emplerContribs = ($line->pension_contribution_employer ?? 0) +
                    ($line->health_contribution_employer ?? 0);
                $employees[$empId]['total_employee_contributions'] += $empContribs;
                $employees[$empId]['total_employer_contributions'] += $emplerContribs;
                $employees[$empId]['employer_cost'] += $line->gross_salary + $emplerContribs;
            }
        }

        // Sort by name
        $result = array_values($employees);
        usort($result, fn($a, $b) => strcmp($a['full_name'], $b['full_name']));

        return response()->json(['data' => $result]);
    }

    /**
     * Export tax summary as CSV.
     */
    public function exportCsv(Request $request, int $company)
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
        ]);

        $year = $validated['year'];
        $month = $validated['month'] ?? null;

        $query = PayrollRun::forCompany($company)
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

        $filename = sprintf('payroll_tax_summary_%s.csv', $month ? "{$year}_{$month}" : $year);
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($runs) {
            $file = fopen('php://output', 'w');
            // UTF-8 BOM for Excel
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [
                'Број',
                'ЕМБГ',
                'Име и Презиме',
                'Период',
                'Бруто плата',
                'Пензиско (вработен)',
                'Пензиско (работодавач)',
                'Здравствено (вработен)',
                'Здравствено (работодавач)',
                'Невработеност',
                'Дополнителен',
                'Данок на доход',
                'Нето плата',
            ]);

            foreach ($runs as $run) {
                foreach ($run->lines as $line) {
                    fputcsv($file, [
                        $line->employee->employee_number ?? '',
                        $line->employee->embg ?? '',
                        $line->employee->full_name ?? '',
                        $run->period_name,
                        number_format(($line->gross_salary ?? 0) / 100, 2, '.', ''),
                        number_format(($line->pension_contribution_employee ?? 0) / 100, 2, '.', ''),
                        number_format(($line->pension_contribution_employer ?? 0) / 100, 2, '.', ''),
                        number_format(($line->health_contribution_employee ?? 0) / 100, 2, '.', ''),
                        number_format(($line->health_contribution_employer ?? 0) / 100, 2, '.', ''),
                        number_format(($line->unemployment_contribution ?? 0) / 100, 2, '.', ''),
                        number_format(($line->additional_contribution ?? 0) / 100, 2, '.', ''),
                        number_format(($line->income_tax_amount ?? 0) / 100, 2, '.', ''),
                        number_format(($line->net_salary ?? 0) / 100, 2, '.', ''),
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
    public function downloadMpinXml(Request $request, int $company): Response|JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $year = $validated['year'];
        $month = $validated['month'];

        $payrollRun = PayrollRun::forCompany($company)
            ->forPeriod($year, $month)
            ->whereIn('status', [
                PayrollRun::STATUS_APPROVED,
                PayrollRun::STATUS_POSTED,
                PayrollRun::STATUS_PAID,
            ])
            ->first();

        if (!$payrollRun) {
            return response()->json([
                'error' => 'No approved payroll run found for the specified period.',
            ], 404);
        }

        $companyModel = Company::find($company);

        $ujpService = new UjpEfilingService();
        $xml = $ujpService->generateMpinXml($payrollRun, $companyModel);

        if (!$ujpService->validateMpinXml($xml)) {
            return response()->json([
                'error' => 'Generated XML failed validation.',
            ], 500);
        }

        $filename = sprintf('MPIN_%s_%s_%02d.xml', $companyModel->unique_hash, $year, $month);

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    /**
     * Download UJP DDV-04 XML for annual employee income report.
     */
    public function downloadDdv04Xml(Request $request, int $company): Response|JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
        ]);

        $year = $validated['year'];

        $payrollRuns = PayrollRun::forCompany($company)
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

        $companyModel = Company::find($company);

        $ujpService = new UjpEfilingService();
        $xml = $ujpService->generateDdv04Xml($year, $companyModel, $payrollRuns);

        if (!$ujpService->validateDdv04Xml($xml)) {
            return response()->json([
                'error' => 'Generated XML failed validation.',
            ], 500);
        }

        $filename = sprintf('DDV04_%s_%s.xml', $companyModel->unique_hash, $year);

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    // ──────────────────────────────────────────────
    // Internal helpers
    // ──────────────────────────────────────────────

    /**
     * Get partner from authenticated request.
     */
    protected function getPartnerFromRequest(Request $request): ?Partner
    {
        $user = $request->user();
        if (!$user) {
            return null;
        }

        if ($user->role === 'super admin') {
            $fakePartner = new Partner();
            $fakePartner->id = 0;
            $fakePartner->user_id = $user->id;
            $fakePartner->name = 'Super Admin';
            $fakePartner->email = $user->email;
            $fakePartner->is_super_admin = true;
            return $fakePartner;
        }

        return Partner::where('user_id', $user->id)->first();
    }

    /**
     * Check if partner has access to a company.
     */
    protected function hasCompanyAccess(Partner $partner, int $companyId): bool
    {
        if ($partner->is_super_admin ?? false) {
            return true;
        }

        return $partner->companies()
            ->where('companies.id', $companyId)
            ->where('partner_company_links.is_active', true)
            ->exists();
    }

    /**
     * Get Macedonian month name.
     */
    private function mkMonthName(int $month): string
    {
        $months = [
            1 => 'Јануари', 2 => 'Февруари', 3 => 'Март', 4 => 'Април',
            5 => 'Мај', 6 => 'Јуни', 7 => 'Јули', 8 => 'Август',
            9 => 'Септември', 10 => 'Октомври', 11 => 'Ноември', 12 => 'Декември',
        ];

        return $months[$month] ?? "Month {$month}";
    }
}

// CLAUDE-CHECKPOINT
