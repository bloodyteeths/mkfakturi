<?php

namespace App\Http\Controllers\V1\Admin\Report;

use App\Domain\Accounting\IfrsAdapter;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Currency;
use App\Models\Expense;
use App\Models\Tax;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use PDF;

/**
 * Controller for exporting financial reports to CSV and PDF formats.
 * Handles Balance Sheet, Income Statement, Trial Balance, Tax Report, and Expenses Report exports.
 */
class ReportExportController extends Controller
{
    /**
     * Export Balance Sheet to CSV or PDF
     */
    public function balanceSheet(Request $request, $hash): Response
    {
        $request->validate([
            'as_of_date' => 'required|date',
            'format' => 'required|in:csv,pdf',
        ]);

        $company = Company::where('unique_hash', $hash)->first();
        $this->authorize('view report', $company);

        // Check if accounting backbone feature is enabled
        if (!$this->isFeatureEnabled()) {
            abort(400, 'Accounting Backbone feature is disabled');
        }

        $locale = CompanySetting::getSetting('language', $company->id);
        App::setLocale($locale);

        $adapter = new IfrsAdapter();
        $asOfDate = $request->as_of_date;
        $balanceSheet = $adapter->getBalanceSheet($company, $asOfDate);

        if (isset($balanceSheet['error'])) {
            abort(400, $balanceSheet['error']);
        }

        $format = $request->format;

        if ($format === 'csv') {
            return $this->exportBalanceSheetCsv($balanceSheet, $company, $asOfDate);
        } else {
            return $this->exportBalanceSheetPdf($balanceSheet, $company, $asOfDate);
        }
    }

    /**
     * Export Income Statement to CSV or PDF
     */
    public function incomeStatement(Request $request, $hash): Response
    {
        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'format' => 'required|in:csv,pdf',
        ]);

        $company = Company::where('unique_hash', $hash)->first();
        $this->authorize('view report', $company);

        if (!$this->isFeatureEnabled()) {
            abort(400, 'Accounting Backbone feature is disabled');
        }

        $locale = CompanySetting::getSetting('language', $company->id);
        App::setLocale($locale);

        $adapter = new IfrsAdapter();
        $fromDate = $request->from_date;
        $toDate = $request->to_date;
        $incomeStatement = $adapter->getIncomeStatement($company, $fromDate, $toDate);

        if (isset($incomeStatement['error'])) {
            abort(400, $incomeStatement['error']);
        }

        $format = $request->format;

        if ($format === 'csv') {
            return $this->exportIncomeStatementCsv($incomeStatement, $company, $fromDate, $toDate);
        } else {
            return $this->exportIncomeStatementPdf($incomeStatement, $company, $fromDate, $toDate);
        }
    }

    /**
     * Export Trial Balance to CSV or PDF
     */
    public function trialBalance(Request $request, $hash): Response
    {
        $request->validate([
            'as_of_date' => 'required|date',
            'format' => 'required|in:csv,pdf',
        ]);

        $company = Company::where('unique_hash', $hash)->first();
        $this->authorize('view report', $company);

        if (!$this->isFeatureEnabled()) {
            abort(400, 'Accounting Backbone feature is disabled');
        }

        $locale = CompanySetting::getSetting('language', $company->id);
        App::setLocale($locale);

        $adapter = new IfrsAdapter();
        $asOfDate = $request->as_of_date;
        $trialBalance = $adapter->getTrialBalance($company, $asOfDate);

        if (isset($trialBalance['error'])) {
            abort(400, $trialBalance['error']);
        }

        $format = $request->format;

        if ($format === 'csv') {
            return $this->exportTrialBalanceCsv($trialBalance, $company, $asOfDate);
        } else {
            return $this->exportTrialBalancePdf($trialBalance, $company, $asOfDate);
        }
    }

    /**
     * Export Tax Summary Report to CSV or PDF
     */
    public function taxSummary(Request $request, $hash): Response
    {
        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'format' => 'required|in:csv,pdf',
        ]);

        $company = Company::where('unique_hash', $hash)->first();
        $this->authorize('view report', $company);

        $locale = CompanySetting::getSetting('language', $company->id);
        App::setLocale($locale);

        $taxTypes = Tax::with('taxType', 'invoice', 'invoiceItem')
            ->whereCompany($company->id)
            ->whereInvoicesFilters($request->only(['from_date', 'to_date']))
            ->taxAttributes()
            ->get();

        $totalAmount = 0;
        foreach ($taxTypes as $taxType) {
            $totalAmount += $taxType->total_tax_amount;
        }

        $format = $request->format;

        if ($format === 'csv') {
            return $this->exportTaxSummaryCsv($taxTypes, $totalAmount, $company, $request->from_date, $request->to_date);
        } else {
            return $this->exportTaxSummaryPdf($taxTypes, $totalAmount, $company, $request->from_date, $request->to_date);
        }
    }

    /**
     * Export Expenses Report to CSV or PDF
     */
    public function expenses(Request $request, $hash): Response
    {
        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'format' => 'required|in:csv,pdf',
        ]);

        $company = Company::where('unique_hash', $hash)->first();
        $this->authorize('view report', $company);

        $locale = CompanySetting::getSetting('language', $company->id);
        App::setLocale($locale);

        $expenseCategories = Expense::with('category')
            ->whereCompanyId($company->id)
            ->applyFilters($request->only(['from_date', 'to_date']))
            ->expensesAttributes()
            ->get();

        $totalAmount = 0;
        foreach ($expenseCategories as $category) {
            $totalAmount += $category->total_amount;
        }

        $format = $request->format;

        if ($format === 'csv') {
            return $this->exportExpensesCsv($expenseCategories, $totalAmount, $company, $request->from_date, $request->to_date);
        } else {
            return $this->exportExpensesPdf($expenseCategories, $totalAmount, $company, $request->from_date, $request->to_date);
        }
    }

    /**
     * Export Balance Sheet to CSV format
     */
    private function exportBalanceSheetCsv($balanceSheet, $company, $asOfDate): Response
    {
        $currency = Currency::findOrFail(CompanySetting::getSetting('currency', $company->id));
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $company->id);
        $formattedDate = Carbon::createFromFormat('Y-m-d', $asOfDate)->translatedFormat($dateFormat);

        $csv = "Balance Sheet\n";
        $csv .= "{$company->name}\n";
        $csv .= "As of {$formattedDate}\n\n";

        // Use nested balance_sheet structure
        $data = $balanceSheet['balance_sheet'] ?? $balanceSheet;
        $totals = $data['totals'] ?? [];

        // Assets
        $csv .= "ASSETS\n";
        $csv .= "Account,Amount\n";
        foreach ($data['assets'] ?? [] as $account) {
            $csv .= "\"{$account['name']}\",{$account['balance']}\n";
        }
        $csv .= "Total Assets,".($totals['assets'] ?? 0)."\n\n";

        // Liabilities
        $csv .= "LIABILITIES\n";
        $csv .= "Account,Amount\n";
        foreach ($data['liabilities'] ?? [] as $account) {
            $csv .= "\"{$account['name']}\",{$account['balance']}\n";
        }
        $csv .= "Total Liabilities,".($totals['liabilities'] ?? 0)."\n\n";

        // Equity
        $csv .= "EQUITY\n";
        $csv .= "Account,Amount\n";
        foreach ($data['equity'] ?? [] as $account) {
            $csv .= "\"{$account['name']}\",{$account['balance']}\n";
        }
        $csv .= "Total Equity,".($totals['equity'] ?? 0)."\n\n";

        $totalLiabilitiesEquity = ($totals['liabilities'] ?? 0) + ($totals['equity'] ?? 0);
        $csv .= "Total Liabilities and Equity,{$totalLiabilitiesEquity}\n";

        $filename = "balance_sheet_{$asOfDate}.csv";

        return response($csv, 200)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->header('Content-Length', strlen($csv));
    }

    /**
     * Export Balance Sheet to PDF format
     */
    private function exportBalanceSheetPdf($balanceSheet, $company, $asOfDate): Response
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $company->id);
        $formattedDate = Carbon::createFromFormat('Y-m-d', $asOfDate)->translatedFormat($dateFormat);
        $currency = Currency::findOrFail(CompanySetting::getSetting('currency', $company->id));

        $colors = [
            'primary_text_color',
            'heading_text_color',
            'section_heading_text_color',
            'border_color',
            'body_text_color',
            'footer_text_color',
            'footer_total_color',
            'footer_bg_color',
            'date_text_color',
        ];
        $colorSettings = CompanySetting::whereIn('option', $colors)
            ->whereCompany($company->id)
            ->get();

        view()->share([
            'company' => $company,
            'balanceSheet' => $balanceSheet,
            'as_of_date' => $formattedDate,
            'colorSettings' => $colorSettings,
            'currency' => $currency,
        ]);

        $pdf = PDF::loadView('app.pdf.reports.balance-sheet');
        return $pdf->download("balance_sheet_{$asOfDate}.pdf");
    }

    /**
     * Export Income Statement to CSV format
     */
    private function exportIncomeStatementCsv($incomeStatement, $company, $fromDate, $toDate): Response
    {
        $currency = Currency::findOrFail(CompanySetting::getSetting('currency', $company->id));
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $company->id);
        $formattedFromDate = Carbon::createFromFormat('Y-m-d', $fromDate)->translatedFormat($dateFormat);
        $formattedToDate = Carbon::createFromFormat('Y-m-d', $toDate)->translatedFormat($dateFormat);

        $csv = "Income Statement\n";
        $csv .= "{$company->name}\n";
        $csv .= "From {$formattedFromDate} to {$formattedToDate}\n\n";

        // Use nested income_statement structure if available
        $data = $incomeStatement['income_statement'] ?? $incomeStatement;
        $totals = $data['totals'] ?? [];

        // Revenue
        $csv .= "REVENUE\n";
        $csv .= "Account,Amount\n";
        foreach ($incomeStatement['revenues'] ?? $data['revenues'] ?? [] as $account) {
            $csv .= "\"{$account['name']}\",{$account['balance']}\n";
        }
        $totalRevenue = $totals['revenue'] ?? $incomeStatement['total_revenue'] ?? 0;
        $csv .= "Total Revenue,{$totalRevenue}\n\n";

        // Expenses
        $csv .= "EXPENSES\n";
        $csv .= "Account,Amount\n";
        foreach ($incomeStatement['expenses'] ?? $data['expenses'] ?? [] as $account) {
            $csv .= "\"{$account['name']}\",{$account['balance']}\n";
        }
        $totalExpenses = $totals['expenses'] ?? $incomeStatement['total_expenses'] ?? 0;
        $csv .= "Total Expenses,{$totalExpenses}\n\n";

        $netIncome = ($totals['revenue'] ?? 0) - ($totals['expenses'] ?? 0);
        if ($netIncome === 0 && isset($incomeStatement['net_income'])) {
            $netIncome = $incomeStatement['net_income'];
        }
        $csv .= "Net Income,{$netIncome}\n";

        $filename = "income_statement_{$fromDate}_{$toDate}.csv";

        return response($csv, 200)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->header('Content-Length', strlen($csv));
    }

    /**
     * Export Income Statement to PDF format
     */
    private function exportIncomeStatementPdf($incomeStatement, $company, $fromDate, $toDate): Response
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $company->id);
        $formattedFromDate = Carbon::createFromFormat('Y-m-d', $fromDate)->translatedFormat($dateFormat);
        $formattedToDate = Carbon::createFromFormat('Y-m-d', $toDate)->translatedFormat($dateFormat);
        $currency = Currency::findOrFail(CompanySetting::getSetting('currency', $company->id));

        $colors = [
            'primary_text_color',
            'heading_text_color',
            'section_heading_text_color',
            'border_color',
            'body_text_color',
            'footer_text_color',
            'footer_total_color',
            'footer_bg_color',
            'date_text_color',
        ];
        $colorSettings = CompanySetting::whereIn('option', $colors)
            ->whereCompany($company->id)
            ->get();

        view()->share([
            'company' => $company,
            'incomeStatement' => $incomeStatement,
            'from_date' => $formattedFromDate,
            'to_date' => $formattedToDate,
            'colorSettings' => $colorSettings,
            'currency' => $currency,
        ]);

        $pdf = PDF::loadView('app.pdf.reports.income-statement');
        return $pdf->download("income_statement_{$fromDate}_{$toDate}.pdf");
    }

    /**
     * Export Trial Balance to CSV format
     */
    private function exportTrialBalanceCsv($trialBalance, $company, $asOfDate): Response
    {
        $currency = Currency::findOrFail(CompanySetting::getSetting('currency', $company->id));
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $company->id);
        $formattedDate = Carbon::createFromFormat('Y-m-d', $asOfDate)->translatedFormat($dateFormat);

        $csv = "Trial Balance\n";
        $csv .= "{$company->name}\n";
        $csv .= "As of {$formattedDate}\n\n";

        $csv .= "Account,Debit,Credit\n";
        foreach ($trialBalance['accounts'] ?? [] as $account) {
            $debit = $account['debit'] > 0 ? $account['debit'] : '';
            $credit = $account['credit'] > 0 ? $account['credit'] : '';
            $csv .= "\"{$account['name']}\",{$debit},{$credit}\n";
        }
        $totalDebit = $trialBalance['trial_balance']['total_debits'] ?? $trialBalance['total_debit'] ?? 0;
        $totalCredit = $trialBalance['trial_balance']['total_credits'] ?? $trialBalance['total_credit'] ?? 0;
        $csv .= "Total,{$totalDebit},{$totalCredit}\n";

        $filename = "trial_balance_{$asOfDate}.csv";

        return response($csv, 200)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->header('Content-Length', strlen($csv));
    }

    /**
     * Export Trial Balance to PDF format
     */
    private function exportTrialBalancePdf($trialBalance, $company, $asOfDate): Response
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $company->id);
        $formattedDate = Carbon::createFromFormat('Y-m-d', $asOfDate)->translatedFormat($dateFormat);
        $currency = Currency::findOrFail(CompanySetting::getSetting('currency', $company->id));

        $colors = [
            'primary_text_color',
            'heading_text_color',
            'section_heading_text_color',
            'border_color',
            'body_text_color',
            'footer_text_color',
            'footer_total_color',
            'footer_bg_color',
            'date_text_color',
        ];
        $colorSettings = CompanySetting::whereIn('option', $colors)
            ->whereCompany($company->id)
            ->get();

        view()->share([
            'company' => $company,
            'trialBalance' => $trialBalance,
            'as_of_date' => $formattedDate,
            'colorSettings' => $colorSettings,
            'currency' => $currency,
        ]);

        $pdf = PDF::loadView('app.pdf.reports.trial-balance');
        return $pdf->download("trial_balance_{$asOfDate}.pdf");
    }

    /**
     * Export Tax Summary to CSV format
     */
    private function exportTaxSummaryCsv($taxTypes, $totalAmount, $company, $fromDate, $toDate): Response
    {
        $currency = Currency::findOrFail(CompanySetting::getSetting('currency', $company->id));
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $company->id);
        $formattedFromDate = Carbon::createFromFormat('Y-m-d', $fromDate)->translatedFormat($dateFormat);
        $formattedToDate = Carbon::createFromFormat('Y-m-d', $toDate)->translatedFormat($dateFormat);

        $csv = "Tax Summary Report\n";
        $csv .= "{$company->name}\n";
        $csv .= "From {$formattedFromDate} to {$formattedToDate}\n\n";

        $csv .= "Tax Type,Tax Amount,Tax Percentage,Total\n";
        foreach ($taxTypes as $tax) {
            $taxName = $tax->taxType->name ?? 'N/A';
            $taxAmount = $tax->amount / 100;
            $taxPercent = $tax->percent ?? 0;
            $totalTax = $tax->total_tax_amount / 100;
            $csv .= "\"{$taxName}\",{$taxAmount},{$taxPercent}%,{$totalTax}\n";
        }
        $formattedTotalTax = $totalAmount / 100;
        $csv .= "Total Tax,,,{$formattedTotalTax}\n";

        $filename = "tax_summary_{$fromDate}_{$toDate}.csv";

        return response($csv, 200)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->header('Content-Length', strlen($csv));
    }

    /**
     * Export Tax Summary to PDF format
     */
    private function exportTaxSummaryPdf($taxTypes, $totalAmount, $company, $fromDate, $toDate): Response
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $company->id);
        $formattedFromDate = Carbon::createFromFormat('Y-m-d', $fromDate)->translatedFormat($dateFormat);
        $formattedToDate = Carbon::createFromFormat('Y-m-d', $toDate)->translatedFormat($dateFormat);
        $currency = Currency::findOrFail(CompanySetting::getSetting('currency', $company->id));

        $colors = [
            'primary_text_color',
            'heading_text_color',
            'section_heading_text_color',
            'border_color',
            'body_text_color',
            'footer_text_color',
            'footer_total_color',
            'footer_bg_color',
            'date_text_color',
        ];
        $colorSettings = CompanySetting::whereIn('option', $colors)
            ->whereCompany($company->id)
            ->get();

        view()->share([
            'taxTypes' => $taxTypes,
            'totalTaxAmount' => $totalAmount,
            'colorSettings' => $colorSettings,
            'company' => $company,
            'from_date' => $formattedFromDate,
            'to_date' => $formattedToDate,
            'currency' => $currency,
        ]);

        $pdf = PDF::loadView('app.pdf.reports.tax-summary');
        return $pdf->download("tax_summary_{$fromDate}_{$toDate}.pdf");
    }

    /**
     * Export Expenses to CSV format
     */
    private function exportExpensesCsv($expenseCategories, $totalAmount, $company, $fromDate, $toDate): Response
    {
        $currency = Currency::findOrFail(CompanySetting::getSetting('currency', $company->id));
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $company->id);
        $formattedFromDate = Carbon::createFromFormat('Y-m-d', $fromDate)->translatedFormat($dateFormat);
        $formattedToDate = Carbon::createFromFormat('Y-m-d', $toDate)->translatedFormat($dateFormat);

        $csv = "Expenses Report\n";
        $csv .= "{$company->name}\n";
        $csv .= "From {$formattedFromDate} to {$formattedToDate}\n\n";

        $csv .= "Category,Amount\n";
        foreach ($expenseCategories as $expense) {
            $categoryName = $expense->category->name ?? 'Uncategorized';
            $amount = $expense->total_amount / 100;
            $csv .= "\"{$categoryName}\",{$amount}\n";
        }
        $formattedTotalExpenses = $totalAmount / 100;
        $csv .= "Total Expenses,{$formattedTotalExpenses}\n";

        $filename = "expenses_report_{$fromDate}_{$toDate}.csv";

        return response($csv, 200)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->header('Content-Length', strlen($csv));
    }

    /**
     * Export Expenses to PDF format
     */
    private function exportExpensesPdf($expenseCategories, $totalAmount, $company, $fromDate, $toDate): Response
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $company->id);
        $formattedFromDate = Carbon::createFromFormat('Y-m-d', $fromDate)->translatedFormat($dateFormat);
        $formattedToDate = Carbon::createFromFormat('Y-m-d', $toDate)->translatedFormat($dateFormat);
        $currency = Currency::findOrFail(CompanySetting::getSetting('currency', $company->id));

        $colors = [
            'primary_text_color',
            'heading_text_color',
            'section_heading_text_color',
            'border_color',
            'body_text_color',
            'footer_text_color',
            'footer_total_color',
            'footer_bg_color',
            'date_text_color',
        ];
        $colorSettings = CompanySetting::whereIn('option', $colors)
            ->whereCompany($company->id)
            ->get();

        view()->share([
            'expenseCategories' => $expenseCategories,
            'colorSettings' => $colorSettings,
            'totalExpense' => $totalAmount,
            'company' => $company,
            'from_date' => $formattedFromDate,
            'to_date' => $formattedToDate,
            'currency' => $currency,
        ]);

        $pdf = PDF::loadView('app.pdf.reports.expenses');
        return $pdf->download("expenses_report_{$fromDate}_{$toDate}.pdf");
    }

    /**
     * Check if accounting backbone feature is enabled
     */
    private function isFeatureEnabled(): bool
    {
        if (function_exists('feature')) {
            return feature('accounting-backbone');
        }

        return config('ifrs.enabled', false) ||
               env('FEATURE_ACCOUNTING_BACKBONE', false);
    }
}

// CLAUDE-CHECKPOINT
