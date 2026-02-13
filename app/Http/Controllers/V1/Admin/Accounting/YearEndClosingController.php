<?php

namespace App\Http\Controllers\V1\Admin\Accounting;

use App\Domain\Accounting\IfrsAdapter;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Currency;
use App\Services\AopReportService;
use App\Services\YearEndClosingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PDF;

/**
 * Year-End Closing Controller
 *
 * Provides API endpoints for the guided year-end closing wizard.
 * Steps: Preflight → Review → Adjust → Close → Reports → Finalize
 */
class YearEndClosingController extends Controller
{
    protected YearEndClosingService $service;

    protected AopReportService $aopService;

    public function __construct(YearEndClosingService $service, AopReportService $aopService)
    {
        $this->service = $service;
        $this->aopService = $aopService;
    }

    /**
     * GET /api/v1/year-end/{year}/preflight
     *
     * Step 1: Run pre-flight checks.
     */
    public function preflight(Request $request, int $year): JsonResponse
    {
        $company = Company::find($request->header('company'));
        if (! $company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        $result = $this->service->getPreflightChecks($company, $year);

        return response()->json($result);
    }

    /**
     * GET /api/v1/year-end/{year}/summary
     *
     * Step 2: Get financial statement summaries for review.
     * Uses pre-closing state to exclude any existing closing entries.
     */
    public function summary(Request $request, int $year): JsonResponse
    {
        $company = Company::find($request->header('company'));
        if (! $company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        $result = $this->service->withPreClosingState($company, $year, function () use ($company, $year) {
            return $this->service->getFinancialSummary($company, $year);
        });

        return response()->json($result);
    }

    /**
     * POST /api/v1/year-end/{year}/closing
     *
     * Step 4: Generate closing entries (preview or commit).
     */
    public function closing(Request $request, int $year): JsonResponse
    {
        $company = Company::find($request->header('company'));
        if (! $company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        $mode = $request->input('mode', 'preview');

        if ($mode === 'preview') {
            $result = $this->service->previewClosingEntries($company, $year);

            return response()->json($result);
        }

        // Commit mode
        try {
            $result = $this->service->generateClosingEntries($company, $year);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * GET /api/v1/year-end/{year}/reports/{type}
     *
     * Step 5: Download reports.
     * Types: balance-sheet, income-statement, trial-balance, tax-summary, notes
     * Query params: format=pdf|csv|json (default: json)
     */
    public function reports(Request $request, int $year, string $type)
    {
        $company = Company::find($request->header('company'));
        if (! $company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        $format = $request->input('format', 'json');

        // Income-related reports need pre-closing P&L data.
        // After year-end closing, P&L accounts are zeroed by closing entries.
        // Balance sheet uses POST-closing state (equity includes transferred P&L).
        $needsPreClosing = in_array($type, ['income-statement', 'tax-summary', 'notes']);

        if ($needsPreClosing) {
            return $this->service->withPreClosingState($company, $year, function () use ($company, $year, $type, $format) {
                return $this->generateReport($company, $year, $type, $format);
            });
        }

        return $this->generateReport($company, $year, $type, $format);
    }

    /**
     * Generate the actual report (called with or without closing entries removed).
     */
    private function generateReport(Company $company, int $year, string $type, string $format)
    {
        $summary = $this->service->getFinancialSummary($company, $year);
        $summary = $this->translateAccountNames($summary);

        if ($type === 'tax-summary') {
            $totalRevenue = $summary['summary']['total_revenue'] ?? 0;
            $totalExpenses = $summary['summary']['total_expenses'] ?? 0;

            $profitBeforeTax = $totalRevenue - $totalExpenses;
            $incomeTax = $profitBeforeTax > 0 ? round($profitBeforeTax * 0.10, 2) : 0;
            $netProfit = $profitBeforeTax - $incomeTax;

            return response()->json([
                'year' => $year,
                'form' => 'ДБ-ВП',
                'form_name' => 'Даночен биланс на вкупен приход',
                'portal' => 'etax.ujp.gov.mk',
                'company' => $company->name,
                'embs' => $company->tax_id ?? '',
                'edb' => $company->vat_number ?? '',
                'period' => "01.01.{$year} - 31.12.{$year}",
                'rows' => [
                    ['row' => 1, 'label' => 'Вкупни приходи', 'value' => $totalRevenue],
                    ['row' => 2, 'label' => 'Приходи од дејноста', 'value' => $totalRevenue],
                    ['row' => 3, 'label' => 'Останати приходи', 'value' => 0],
                    ['row' => 4, 'label' => 'Финансиски приходи', 'value' => 0],
                    ['row' => 5, 'label' => 'Основица за оданочување', 'value' => max($profitBeforeTax, 0)],
                    ['row' => 6, 'label' => 'Стапка на данок', 'value' => '10%'],
                    ['row' => 7, 'label' => 'Пресметан данок', 'value' => $incomeTax],
                    ['row' => 8, 'label' => 'Уплатен аконтативен данок', 'value' => 0],
                    ['row' => 9, 'label' => 'Разлика за доплата', 'value' => $incomeTax],
                ],
                'summary' => [
                    'total_revenue' => $totalRevenue,
                    'total_expenses' => $totalExpenses,
                    'profit_before_tax' => $profitBeforeTax,
                    'income_tax' => $incomeTax,
                    'net_profit' => $netProfit,
                ],
            ]);
        }

        if ($format === 'json') {
            return response()->json(['year' => $year, 'type' => $type, 'data' => $summary]);
        }

        // PDF generation
        $currency = null;
        $dateFormat = 'd.m.Y';

        try {
            $currencyId = CompanySetting::getSetting('currency', $company->id);
            $currency = $currencyId ? Currency::find($currencyId) : null;
            $dateFormat = CompanySetting::getSetting('carbon_date_format', $company->id) ?: 'd.m.Y';
        } catch (\Exception $e) {
            // Use defaults
        }

        $colorSettings = CompanySetting::whereIn('option', [
            'primary_text_color', 'heading_text_color', 'section_heading_text_color',
            'border_color', 'body_text_color', 'footer_text_color',
            'footer_total_color', 'footer_bg_color', 'date_text_color',
        ])->whereCompany($company->id)->get();

        view()->share([
            'company' => $company,
            'colorSettings' => $colorSettings,
            'currency' => $currency,
        ]);

        return match ($type) {
            'balance-sheet' => $this->downloadBalanceSheetPdf($company, $year, $dateFormat),
            'income-statement' => $this->downloadIncomeStatementPdf($company, $year, $dateFormat),
            'trial-balance' => $this->downloadTrialBalancePdf($summary, $company, $year, $dateFormat),
            default => $this->downloadGenericCsv($summary, $type, $year, $company),
        };
    }

    private function downloadBalanceSheetPdf(Company $company, int $year, string $dateFormat): Response
    {
        $asOfDate = \Carbon\Carbon::create($year, 12, 31)->translatedFormat($dateFormat);
        $aopData = $this->aopService->getBalanceSheetAop($company, $year);

        view()->share([
            'aopData' => $aopData,
            'as_of_date' => $asOfDate,
        ]);

        $pdf = PDF::loadView('app.pdf.reports.balance-sheet');

        return $pdf->download("balance_sheet_{$year}.pdf");
    }

    private function downloadIncomeStatementPdf(Company $company, int $year, string $dateFormat): Response
    {
        $fromDate = \Carbon\Carbon::create($year, 1, 1)->translatedFormat($dateFormat);
        $toDate = \Carbon\Carbon::create($year, 12, 31)->translatedFormat($dateFormat);
        $aopData = $this->aopService->getIncomeStatementAop($company, $year);

        view()->share([
            'aopData' => $aopData,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);

        $pdf = PDF::loadView('app.pdf.reports.income-statement');

        return $pdf->download("income_statement_{$year}.pdf");
    }

    private function downloadTrialBalancePdf(array $summary, Company $company, int $year, string $dateFormat): Response
    {
        $asOfDate = \Carbon\Carbon::create($year, 12, 31)->translatedFormat($dateFormat);

        // Blade template expects $trialBalance['trial_balance']['accounts'] (double-nested)
        view()->share([
            'trialBalance' => ['trial_balance' => $summary['trial_balance'] ?? []],
            'as_of_date' => $asOfDate,
        ]);

        $pdf = PDF::loadView('app.pdf.reports.trial-balance');

        return $pdf->download("trial_balance_{$year}.pdf");
    }

    private function downloadGenericCsv(array $summary, string $type, int $year, Company $company): Response
    {
        // Closing entries are already soft-deleted by reports() for income-related types,
        // so getFinancialSummary returns pre-closing data with correct revenue/expenses.
        $totalRevenue = $summary['summary']['total_revenue'] ?? 0;
        $totalExpenses = $summary['summary']['total_expenses'] ?? 0;

        $profitBeforeTax = $totalRevenue - $totalExpenses;
        $incomeTax = $profitBeforeTax > 0 ? round($profitBeforeTax * 0.10, 2) : 0;
        $netProfit = $profitBeforeTax - $incomeTax;

        // ДБ-ВП format (Даночен биланс на вкупен приход)
        $csvContent = "\xEF\xBB\xBF"; // UTF-8 BOM for Macedonian characters
        $csvContent .= "Образец,ДБ-ВП\n";
        $csvContent .= "Даночен период,01.01.{$year} - 31.12.{$year}\n";
        $csvContent .= "Портал,etax.ujp.gov.mk\n\n";
        $csvContent .= "Ред.бр.,Позиција,Износ\n";
        $csvContent .= "1,Вкупни приходи (AOP 246),{$totalRevenue}\n";
        $csvContent .= "2,Вкупни расходи (AOP 247),{$totalExpenses}\n";
        $csvContent .= "3,Добивка/загуба пред оданочување (AOP 248/249),{$profitBeforeTax}\n";
        $csvContent .= "4,Данок на добивка 10% (AOP 250),{$incomeTax}\n";
        $csvContent .= "5,Нето добивка/загуба (AOP 255/256),{$netProfit}\n";

        $filename = $type === 'notes'
            ? "beleshki_{$year}.csv"
            : "db_vp_{$year}.csv";

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * POST /api/v1/year-end/{year}/finalize
     *
     * Step 6: Lock the fiscal year.
     */
    public function finalize(Request $request, int $year): JsonResponse
    {
        $company = Company::find($request->header('company'));
        if (! $company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        try {
            $result = $this->service->finalize($company, $year, auth()->id());

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /api/v1/year-end/{year}/undo
     *
     * Undo year-end closing (within 24h).
     */
    public function undo(Request $request, int $year): JsonResponse
    {
        $company = Company::find($request->header('company'));
        if (! $company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        try {
            $result = $this->service->undo($company, $year, auth()->id());

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
    /**
     * Translate account names to Macedonian in the financial summary.
     *
     * Cached pre-closing summaries may have English names from the IFRS library.
     * This post-processes all account name fields to ensure Macedonian output.
     */
    private function translateAccountNames(array $summary): array
    {
        $map = IfrsAdapter::MK_ACCOUNT_TYPES;

        // Build reverse lookup: English name → Macedonian name
        // IFRS library uses names like "Receivable", "Equity", "Operating Expense"
        $reverseMap = [];
        foreach ($map as $constant => $mkName) {
            // "OPERATING_EXPENSE" → "Operating Expense"
            $englishName = str_replace('_', ' ', ucwords(strtolower($constant), '_'));
            $reverseMap[$englishName] = $mkName;
            // Also map by raw constant key
            $reverseMap[$constant] = $mkName;
            // Also map common variations (title case without underscores)
            $reverseMap[ucfirst(strtolower(str_replace('_', ' ', $constant)))] = $mkName;
        }

        $translateList = function (array &$items) use ($reverseMap, $map) {
            foreach ($items as &$item) {
                if (! isset($item['name'])) {
                    continue;
                }
                $name = $item['name'];
                // Already Macedonian (contains Cyrillic)
                if (preg_match('/[\x{0400}-\x{04FF}]/u', $name)) {
                    continue;
                }
                // Try direct match by code field (IFRS constant)
                if (isset($item['code']) && isset($map[$item['code']])) {
                    $item['name'] = $map[$item['code']];
                } elseif (isset($reverseMap[$name])) {
                    $item['name'] = $reverseMap[$name];
                }
            }
        };

        // Balance sheet sections
        if (isset($summary['balance_sheet']['assets'])) {
            $translateList($summary['balance_sheet']['assets']);
        }
        if (isset($summary['balance_sheet']['equity'])) {
            $translateList($summary['balance_sheet']['equity']);
        }
        if (isset($summary['balance_sheet']['liabilities'])) {
            $translateList($summary['balance_sheet']['liabilities']);
        }

        // Income statement sections
        if (isset($summary['income_statement']['revenues'])) {
            $translateList($summary['income_statement']['revenues']);
        }
        if (isset($summary['income_statement']['expenses'])) {
            $translateList($summary['income_statement']['expenses']);
        }

        // Trial balance
        if (isset($summary['trial_balance']['accounts'])) {
            $translateList($summary['trial_balance']['accounts']);
        }

        return $summary;
    }
}
// CLAUDE-CHECKPOINT
