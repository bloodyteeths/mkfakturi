<?php

namespace App\Services;

use App\Domain\Accounting\IfrsAdapter;
use App\Models\Company;
use App\Models\Partner;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * P8-03: Bulk Reporting Service
 *
 * Provides multi-company and consolidated report generation
 * for partners managing multiple client companies.
 */
class BulkReportingService
{
    /**
     * Valid report types supported by this service.
     */
    public const REPORT_TYPES = ['trial_balance', 'profit_loss', 'balance_sheet'];

    public function __construct(
        private IfrsAdapter $ifrsAdapter
    ) {}

    /**
     * Generate a report for each requested company, returned separately.
     *
     * @param  Partner  $partner  The partner requesting the reports
     * @param  array<int>  $companyIds  IDs of companies to report on
     * @param  string  $reportType  One of: trial_balance, profit_loss, balance_sheet
     * @param  Carbon  $fromDate  Start of reporting period
     * @param  Carbon  $toDate  End of reporting period
     * @return array{companies: array<array{id: int, name: string, report_data: array}>}
     *
     * @throws AuthorizationException
     */
    public function multiCompanyReport(
        Partner $partner,
        array $companyIds,
        string $reportType,
        Carbon $fromDate,
        Carbon $toDate
    ): array {
        $this->validateAccess($partner, $companyIds);

        $companies = Company::whereIn('id', $companyIds)->get();
        $results = [];

        foreach ($companies as $company) {
            $reportData = $this->generateReport($company, $reportType, $fromDate, $toDate);

            $results[] = [
                'id' => $company->id,
                'name' => $company->name,
                'report_data' => $reportData,
            ];
        }

        return ['companies' => $results];
    }

    /**
     * Generate a consolidated balance sheet across multiple companies.
     *
     * Aggregates total assets, liabilities, equity, revenue, and expenses.
     *
     * @param  Partner  $partner  The partner requesting the report
     * @param  array<int>  $companyIds  IDs of companies to consolidate
     * @param  Carbon  $fromDate  Start of reporting period
     * @param  Carbon  $toDate  End of reporting period
     * @return array{consolidated: array, company_count: int, companies: array}
     *
     * @throws AuthorizationException
     */
    public function consolidatedReport(
        Partner $partner,
        array $companyIds,
        Carbon $fromDate,
        Carbon $toDate
    ): array {
        $this->validateAccess($partner, $companyIds);

        $companies = Company::whereIn('id', $companyIds)->get();

        $totalAssets = 0;
        $totalLiabilities = 0;
        $totalEquity = 0;
        $totalRevenue = 0;
        $totalExpenses = 0;

        $companyBreakdown = [];

        foreach ($companies as $company) {
            // Generate balance sheet for totals
            $balanceSheet = $this->ifrsAdapter->getBalanceSheet($company, $toDate->toDateString());

            // Generate income statement for revenue/expenses
            $incomeStatement = $this->ifrsAdapter->getIncomeStatement(
                $company,
                $fromDate->toDateString(),
                $toDate->toDateString()
            );

            $companyAssets = 0;
            $companyLiabilities = 0;
            $companyEquity = 0;
            $companyRevenue = 0;
            $companyExpenses = 0;

            // Extract balance sheet totals (skip error responses)
            if (! isset($balanceSheet['error'])) {
                $bsTotals = $balanceSheet['balance_sheet']['totals'] ?? [];
                $companyAssets = $bsTotals['assets'] ?? 0;
                $companyLiabilities = $bsTotals['liabilities'] ?? 0;
                $companyEquity = $bsTotals['equity'] ?? 0;
            }

            // Extract income statement totals (skip error responses)
            if (! isset($incomeStatement['error'])) {
                $isTotals = $incomeStatement['income_statement']['totals'] ?? [];
                $companyRevenue = $isTotals['revenue'] ?? 0;
                $companyExpenses = $isTotals['expenses'] ?? 0;
            }

            $totalAssets += $companyAssets;
            $totalLiabilities += $companyLiabilities;
            $totalEquity += $companyEquity;
            $totalRevenue += $companyRevenue;
            $totalExpenses += $companyExpenses;

            $companyBreakdown[] = [
                'id' => $company->id,
                'name' => $company->name,
                'assets' => $companyAssets,
                'liabilities' => $companyLiabilities,
                'equity' => $companyEquity,
                'revenue' => $companyRevenue,
                'expenses' => $companyExpenses,
                'net_income' => $companyRevenue - $companyExpenses,
            ];
        }

        return [
            'consolidated' => [
                'total_assets' => $totalAssets,
                'total_liabilities' => $totalLiabilities,
                'total_equity' => $totalEquity,
                'total_revenue' => $totalRevenue,
                'total_expenses' => $totalExpenses,
                'net_income' => $totalRevenue - $totalExpenses,
            ],
            'company_count' => $companies->count(),
            'companies' => $companyBreakdown,
        ];
    }

    /**
     * Export a multi-company report as CSV or JSON.
     *
     * @param  Partner  $partner  The partner requesting the export
     * @param  array<int>  $companyIds  IDs of companies to export
     * @param  string  $reportType  One of: trial_balance, profit_loss, balance_sheet
     * @param  Carbon  $fromDate  Start of reporting period
     * @param  Carbon  $toDate  End of reporting period
     * @param  string  $format  Export format: 'csv' or 'json'
     * @return StreamedResponse|array
     *
     * @throws AuthorizationException
     */
    public function exportReport(
        Partner $partner,
        array $companyIds,
        string $reportType,
        Carbon $fromDate,
        Carbon $toDate,
        string $format
    ): StreamedResponse|array {
        $reportData = $this->multiCompanyReport($partner, $companyIds, $reportType, $fromDate, $toDate);

        if ($format === 'csv') {
            return $this->generateCsvExport($reportData, $reportType, $fromDate, $toDate);
        }

        // JSON: return the raw data with metadata
        return [
            'report_type' => $reportType,
            'from_date' => $fromDate->toDateString(),
            'to_date' => $toDate->toDateString(),
            'generated_at' => now()->toIso8601String(),
            'data' => $reportData,
        ];
    }

    /**
     * Validate that the partner has active access to all requested companies.
     *
     * @param  Partner  $partner  The partner to validate
     * @param  array<int>  $companyIds  IDs to check access for
     *
     * @throws AuthorizationException
     */
    private function validateAccess(Partner $partner, array $companyIds): void
    {
        // Super admin has access to all companies
        if ($partner->is_super_admin ?? false) {
            return;
        }

        $accessibleIds = $partner->companies()
            ->where('partner_company_links.is_active', true)
            ->whereIn('companies.id', $companyIds)
            ->pluck('companies.id')
            ->toArray();

        $missingIds = array_diff($companyIds, $accessibleIds);

        if (! empty($missingIds)) {
            Log::warning('Partner bulk report access denied', [
                'partner_id' => $partner->id,
                'requested_ids' => $companyIds,
                'missing_ids' => $missingIds,
            ]);

            throw new AuthorizationException(
                'Access denied to one or more companies: ' . implode(', ', $missingIds)
            );
        }
    }

    /**
     * Generate a single report for a company using the IfrsAdapter.
     *
     * @param  Company  $company  The company to report on
     * @param  string  $reportType  One of: trial_balance, profit_loss, balance_sheet
     * @param  Carbon  $fromDate  Start date (used for profit_loss)
     * @param  Carbon  $toDate  End date / as-of date
     * @return array The report data from IfrsAdapter
     */
    private function generateReport(
        Company $company,
        string $reportType,
        Carbon $fromDate,
        Carbon $toDate
    ): array {
        return match ($reportType) {
            'trial_balance' => $this->ifrsAdapter->getTrialBalance($company, $toDate->toDateString()),
            'balance_sheet' => $this->ifrsAdapter->getBalanceSheet($company, $toDate->toDateString()),
            'profit_loss' => $this->ifrsAdapter->getIncomeStatement(
                $company,
                $fromDate->toDateString(),
                $toDate->toDateString()
            ),
            default => ['error' => "Unknown report type: {$reportType}"],
        };
    }

    /**
     * Generate a CSV streamed response from multi-company report data.
     *
     * @param  array  $reportData  The multi-company report data
     * @param  string  $reportType  The type of report
     * @param  Carbon  $fromDate  Start of reporting period
     * @param  Carbon  $toDate  End of reporting period
     */
    private function generateCsvExport(
        array $reportData,
        string $reportType,
        Carbon $fromDate,
        Carbon $toDate
    ): StreamedResponse {
        $filename = sprintf(
            'bulk_%s_%s_%s.csv',
            $reportType,
            $fromDate->format('Y-m-d'),
            $toDate->format('Y-m-d')
        );

        return new StreamedResponse(function () use ($reportData, $reportType) {
            $handle = fopen('php://output', 'w');

            // Write header row based on report type
            $headers = $this->getCsvHeaders($reportType);
            fputcsv($handle, $headers);

            // Write one row per company
            foreach ($reportData['companies'] ?? [] as $company) {
                $row = $this->getCsvRow($company, $reportType);
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * Get CSV header columns for the given report type.
     *
     * @param  string  $reportType  The report type
     * @return array<string>
     */
    private function getCsvHeaders(string $reportType): array
    {
        $base = ['Company ID', 'Company Name'];

        return match ($reportType) {
            'trial_balance' => array_merge($base, ['Total Debits', 'Total Credits', 'Is Balanced']),
            'balance_sheet' => array_merge($base, ['Total Assets', 'Total Liabilities', 'Total Equity']),
            'profit_loss' => array_merge($base, ['Total Revenue', 'Total Expenses', 'Net Income']),
            default => $base,
        };
    }

    /**
     * Extract a CSV row from a single company's report data.
     *
     * @param  array  $company  The company report entry
     * @param  string  $reportType  The report type
     * @return array<mixed>
     */
    private function getCsvRow(array $company, string $reportType): array
    {
        $base = [$company['id'], $company['name']];
        $data = $company['report_data'] ?? [];

        // If there was an error generating this company's report, show zeros
        if (isset($data['error'])) {
            return match ($reportType) {
                'trial_balance' => array_merge($base, [0, 0, 'N/A']),
                'balance_sheet' => array_merge($base, [0, 0, 0]),
                'profit_loss' => array_merge($base, [0, 0, 0]),
                default => $base,
            };
        }

        return match ($reportType) {
            'trial_balance' => array_merge($base, [
                $data['total_debits'] ?? 0,
                $data['total_credits'] ?? 0,
                ($data['is_balanced'] ?? false) ? 'Yes' : 'No',
            ]),
            'balance_sheet' => array_merge($base, [
                $data['balance_sheet']['totals']['assets'] ?? 0,
                $data['balance_sheet']['totals']['liabilities'] ?? 0,
                $data['balance_sheet']['totals']['equity'] ?? 0,
            ]),
            'profit_loss' => array_merge($base, [
                $data['income_statement']['totals']['revenue'] ?? 0,
                $data['income_statement']['totals']['expenses'] ?? 0,
                ($data['income_statement']['totals']['revenue'] ?? 0) - ($data['income_statement']['totals']['expenses'] ?? 0),
            ]),
            default => $base,
        };
    }
}

