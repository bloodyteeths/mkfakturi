<?php

namespace App\Http\Controllers\V1\Admin\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Currency;
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

    public function __construct(YearEndClosingService $service)
    {
        $this->service = $service;
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
     */
    public function summary(Request $request, int $year): JsonResponse
    {
        $company = Company::find($request->header('company'));
        if (! $company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        $result = $this->service->getFinancialSummary($company, $year);

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
        $summary = $this->service->getFinancialSummary($company, $year);

        if ($type === 'tax-summary') {
            return response()->json([
                'year' => $year,
                'company' => $company->name,
                'tax_id' => $company->tax_id ?? '',
                'revenue' => $summary['summary']['total_revenue'],
                'expenses' => $summary['summary']['total_expenses'],
                'profit_before_tax' => $summary['summary']['net_profit_before_tax'],
                'income_tax_rate' => $summary['summary']['income_tax_rate'],
                'income_tax' => $summary['summary']['income_tax'],
                'net_profit' => $summary['summary']['net_profit_after_tax'],
                'form' => 'ДБ-ВП',
                'portal' => 'etax.ujp.gov.mk',
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
            'balance-sheet' => $this->downloadBalanceSheetPdf($summary, $company, $year, $dateFormat),
            'income-statement' => $this->downloadIncomeStatementPdf($summary, $company, $year, $dateFormat),
            'trial-balance' => $this->downloadTrialBalancePdf($summary, $company, $year, $dateFormat),
            default => $this->downloadGenericCsv($summary, $type, $year),
        };
    }

    private function downloadBalanceSheetPdf(array $summary, Company $company, int $year, string $dateFormat): Response
    {
        $asOfDate = \Carbon\Carbon::create($year, 12, 31)->translatedFormat($dateFormat);

        view()->share([
            'balanceSheet' => $summary['balance_sheet'] ?? [],
            'as_of_date' => $asOfDate,
        ]);

        $pdf = PDF::loadView('app.pdf.reports.balance-sheet');

        return $pdf->download("balance_sheet_{$year}.pdf");
    }

    private function downloadIncomeStatementPdf(array $summary, Company $company, int $year, string $dateFormat): Response
    {
        $fromDate = \Carbon\Carbon::create($year, 1, 1)->translatedFormat($dateFormat);
        $toDate = \Carbon\Carbon::create($year, 12, 31)->translatedFormat($dateFormat);

        view()->share([
            'incomeStatement' => $summary['income_statement'] ?? [],
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);

        $pdf = PDF::loadView('app.pdf.reports.income-statement');

        return $pdf->download("income_statement_{$year}.pdf");
    }

    private function downloadTrialBalancePdf(array $summary, Company $company, int $year, string $dateFormat): Response
    {
        $asOfDate = \Carbon\Carbon::create($year, 12, 31)->translatedFormat($dateFormat);

        view()->share([
            'trialBalance' => $summary['trial_balance'] ?? [],
            'as_of_date' => $asOfDate,
        ]);

        $pdf = PDF::loadView('app.pdf.reports.trial-balance');

        return $pdf->download("trial_balance_{$year}.pdf");
    }

    private function downloadGenericCsv(array $summary, string $type, int $year): Response
    {
        $csvContent = "Type,{$type}\nYear,{$year}\n\n";
        $csvContent .= "Revenue," . ($summary['summary']['total_revenue'] ?? 0) . "\n";
        $csvContent .= "Expenses," . ($summary['summary']['total_expenses'] ?? 0) . "\n";
        $csvContent .= "Net Profit Before Tax," . ($summary['summary']['net_profit_before_tax'] ?? 0) . "\n";
        $csvContent .= "Income Tax," . ($summary['summary']['income_tax'] ?? 0) . "\n";
        $csvContent .= "Net Profit After Tax," . ($summary['summary']['net_profit_after_tax'] ?? 0) . "\n";

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$type}_{$year}.csv\"",
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
}
// CLAUDE-CHECKPOINT
