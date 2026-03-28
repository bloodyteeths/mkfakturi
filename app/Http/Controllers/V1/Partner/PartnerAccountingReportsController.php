<?php

namespace App\Http\Controllers\V1\Partner;

use App\Domain\Accounting\IfrsAdapter;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Partner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PDF;

/**
 * Partner Accounting Reports Controller
 *
 * Provides IFRS-compliant financial reports for partner's client companies.
 */
class PartnerAccountingReportsController extends Controller
{
    protected IfrsAdapter $ifrsAdapter;

    public function __construct(IfrsAdapter $ifrsAdapter)
    {
        $this->ifrsAdapter = $ifrsAdapter;
    }

    /**
     * Get General Ledger Report
     */
    public function generalLedger(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }

        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $companyModel = Company::find($company);
        if (!$companyModel) {
            return response()->json(['success' => false, 'message' => 'Company not found'], 404);
        }

        $fromDate = $request->query('from_date', now()->startOfYear()->toDateString());
        $toDate = $request->query('to_date', now()->toDateString());
        $accountId = $request->query('account_id');
        $accountCode = $request->query('account_code');

        if (!$accountId && !$accountCode) {
            return response()->json([
                'success' => false,
                'message' => 'Account ID or code is required',
            ], 400);
        }

        // If account_code provided, look up by code in IFRS; otherwise try by ID
        // The account_id from the dropdown is from the `accounts` table (App\Models\Account),
        // but IfrsAdapter needs IFRS account IDs. Pass code so it can find the right IFRS account.
        if ($accountCode) {
            $ledger = $this->ifrsAdapter->getGeneralLedger($companyModel, null, $fromDate, $toDate, $accountCode);
        } else {
            // Try to get the account code from the App\Models\Account table
            $appAccount = \App\Models\Account::where('company_id', $company)->find($accountId);
            if ($appAccount && $appAccount->code) {
                $ledger = $this->ifrsAdapter->getGeneralLedger($companyModel, null, $fromDate, $toDate, $appAccount->code);
            } else {
                $ledger = $this->ifrsAdapter->getGeneralLedger($companyModel, (int) $accountId, $fromDate, $toDate);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $ledger,
        ]);
    }

    /**
     * Get Journal Entries Report
     */
    public function journalEntries(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }

        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $companyModel = Company::find($company);
        if (!$companyModel) {
            return response()->json(['success' => false, 'message' => 'Company not found'], 404);
        }

        $fromDate = $request->query('from_date', now()->startOfYear()->toDateString());
        $toDate = $request->query('to_date', now()->toDateString());

        $entries = $this->ifrsAdapter->getJournalEntries($companyModel, $fromDate, $toDate);

        // Filter by account if specified
        $filterAccountCode = $request->query('account_code');
        if (!$filterAccountCode && $request->query('account_id')) {
            $account = \App\Models\Account::find($request->query('account_id'));
            $filterAccountCode = $account?->code;
        }

        if ($filterAccountCode && isset($entries['entries'])) {
            $entries['entries'] = array_values(array_filter($entries['entries'], function ($entry) use ($filterAccountCode) {
                foreach ($entry['lines'] as $line) {
                    if ($line['account_code'] === $filterAccountCode) {
                        return true;
                    }
                }
                return false;
            }));
        }

        return response()->json([
            'success' => true,
            'data' => $entries,
        ]);
    }

    /**
     * Get Trial Balance Report (6-column: Opening/Period/Closing)
     */
    public function trialBalance(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }

        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $companyModel = Company::find($company);
        if (!$companyModel) {
            return response()->json(['success' => false, 'message' => 'Company not found'], 404);
        }

        $fromDate = $request->query('from_date', now()->startOfYear()->toDateString());
        $toDate = $request->query('to_date', $request->query('as_of_date', now()->toDateString()));

        $trialBalance = $this->ifrsAdapter->getTrialBalanceSixColumn($companyModel, $fromDate, $toDate);

        if (isset($trialBalance['error'])) {
            return response()->json($trialBalance, 400);
        }

        return response()->json([
            'success' => true,
            'trial_balance' => $trialBalance,
        ]);
    }

    /**
     * Export Trial Balance as PDF
     */
    public function trialBalanceExport(Request $request, int $company): Response
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            abort(404, 'Partner not found');
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            abort(403, 'No access to this company');
        }

        $companyModel = Company::find($company);
        if (!$companyModel) {
            abort(404, 'Company not found');
        }

        $companyModel->load('address');

        $fromDate = $request->query('from_date', now()->startOfYear()->toDateString());
        $toDate = $request->query('to_date', now()->toDateString());

        $trialBalance = $this->ifrsAdapter->getTrialBalanceSixColumn($companyModel, $fromDate, $toDate);

        if (isset($trialBalance['error'])) {
            abort(400, $trialBalance['error']);
        }

        $currency = \App\Models\Currency::find(
            CompanySetting::getSetting('currency', $company)
        );

        view()->share([
            'company' => $companyModel,
            'trialBalance' => $trialBalance,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'report_period' => $fromDate . ' - ' . $toDate,
            'currency' => $currency,
        ]);

        $pdf = PDF::loadView('app.pdf.reports.trial-balance');

        return $pdf->download("bruto_bilans_{$fromDate}_{$toDate}.pdf");
    }

    /**
     * Get Balance Sheet Report
     */
    public function balanceSheet(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }

        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $companyModel = Company::find($company);
        if (!$companyModel) {
            return response()->json(['success' => false, 'message' => 'Company not found'], 404);
        }

        $asOfDate = $request->query('as_of_date', now()->toDateString());

        $balanceSheet = $this->ifrsAdapter->getBalanceSheet($companyModel, $asOfDate);

        if (isset($balanceSheet['error'])) {
            return response()->json($balanceSheet, 400);
        }

        return response()->json([
            'success' => true,
            'balance_sheet' => $balanceSheet,
        ]);
    }

    /**
     * Get Income Statement Report
     */
    public function incomeStatement(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }

        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $companyModel = Company::find($company);
        if (!$companyModel) {
            return response()->json(['success' => false, 'message' => 'Company not found'], 404);
        }

        $fromDate = $request->query('from_date', now()->startOfYear()->toDateString());
        $toDate = $request->query('to_date', now()->toDateString());

        $incomeStatement = $this->ifrsAdapter->getIncomeStatement($companyModel, $fromDate, $toDate);

        if (isset($incomeStatement['error'])) {
            return response()->json($incomeStatement, 400);
        }

        return response()->json([
            'success' => true,
            'income_statement' => $incomeStatement,
        ]);
    }

    /**
     * Check IFRS accounting status for a client company.
     */
    public function cashFlow(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $companyModel = Company::find($company);
        if (!$companyModel) {
            return response()->json(['success' => false, 'message' => 'Company not found'], 404);
        }

        $startDate = $request->query('start_date', now()->startOfYear()->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());

        $result = $this->ifrsAdapter->getCashFlowStatement($companyModel, $startDate, $endDate);

        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * Export Cash Flow Statement as PDF (UJP Образец 38)
     */
    public function cashFlowExport(Request $request, int $company): Response
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            abort(404, 'Partner not found');
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            abort(403, 'No access to this company');
        }

        $companyModel = Company::find($company);
        if (!$companyModel) {
            abort(404, 'Company not found');
        }

        $companyModel->load('address');

        $startDate = $request->query('start_date', now()->startOfYear()->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());

        // Current period
        $current = $this->ifrsAdapter->getCashFlowStatement($companyModel, $startDate, $endDate);

        // Previous period (same length, one year back)
        $prevStart = date('Y-m-d', strtotime($startDate . ' -1 year'));
        $prevEnd = date('Y-m-d', strtotime($endDate . ' -1 year'));
        $previous = $this->ifrsAdapter->getCashFlowStatement($companyModel, $prevStart, $prevEnd);

        // Build AOP rows from config
        $aopConfig = config('ujp_aop.obrazec_38');
        $aopRows = [];

        foreach (['operating', 'investing', 'financing', 'summary'] as $section) {
            foreach ($aopConfig[$section] as $row) {
                $currentVal = 0;
                $previousVal = 0;

                if (!empty($row['data_key'])) {
                    $keys = explode('.', $row['data_key']);
                    $currentVal = $current[$keys[0]][$keys[1]] ?? 0;
                    $previousVal = $previous[$keys[0]][$keys[1]] ?? 0;
                }

                $aopRows[] = [
                    'aop' => $row['aop'],
                    'label' => $row['label'],
                    'indent' => $row['indent'],
                    'is_total' => $row['is_total'] ?? false,
                    'is_summary' => $section === 'summary',
                    'data_key' => $row['data_key'] ?? null,
                    'current' => round($currentVal, 2),
                    'previous' => round($previousVal, 2),
                ];
            }
        }

        $currency = CompanySetting::getSetting('currency', $company);

        view()->share([
            'company' => $companyModel,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'aopRows' => $aopRows,
            'currency' => $currency,
        ]);

        $pdf = PDF::loadView('app.pdf.reports.cash-flow');

        return $pdf->download("cash_flow_{$startDate}_{$endDate}.pdf");
    }

    // CLAUDE-CHECKPOINT

    public function equityChanges(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $companyModel = Company::find($company);
        if (!$companyModel) {
            return response()->json(['success' => false, 'message' => 'Company not found'], 404);
        }

        $year = (int) $request->query('year', now()->year);
        $result = $this->ifrsAdapter->getEquityChanges($companyModel, $year);

        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * Export Equity Changes as PDF
     */
    public function equityChangesExport(Request $request, int $company): Response
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            abort(404, 'Partner not found');
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            abort(403, 'No access to this company');
        }

        $companyModel = Company::find($company);
        if (!$companyModel) {
            abort(404, 'Company not found');
        }

        $companyModel->load('address');

        $year = (int) $request->query('year', now()->year);
        $current = $this->ifrsAdapter->getEquityChanges($companyModel, $year);
        $previous = $this->ifrsAdapter->getEquityChanges($companyModel, $year - 1);

        $currency = CompanySetting::getSetting('currency', $company);

        view()->share([
            'company' => $companyModel,
            'year' => $year,
            'current' => $current,
            'previous' => $previous,
            'currency' => $currency,
        ]);

        $pdf = PDF::loadView('app.pdf.reports.equity-changes');

        return $pdf->download("equity_changes_{$year}.pdf");
    }

    /**
     * Get Sub-Ledger (Аналитика по комитент) for an account.
     */
    public function subLedger(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }

        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $companyModel = Company::find($company);
        if (!$companyModel) {
            return response()->json(['success' => false, 'message' => 'Company not found'], 404);
        }

        $accountCode = $request->query('account_code');
        if (!$accountCode && $request->query('account_id')) {
            $appAccount = \App\Models\Account::where('company_id', $company)->find($request->query('account_id'));
            $accountCode = $appAccount?->code;
        }

        if (!$accountCode) {
            return response()->json(['success' => false, 'message' => 'Account code is required'], 400);
        }

        $fromDate = $request->query('from_date', now()->startOfYear()->toDateString());
        $toDate = $request->query('to_date', now()->toDateString());

        $result = $this->ifrsAdapter->getSubLedger($companyModel, $accountCode, $fromDate, $toDate);

        if (isset($result['error'])) {
            return response()->json(['success' => false, 'message' => $result['error']], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    // CLAUDE-CHECKPOINT

    public function ifrsStatus(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }

        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $ifrsEnabled = CompanySetting::getSetting('ifrs_enabled', $company);
        $isEnabled = $ifrsEnabled === 'YES' || $ifrsEnabled === true || $ifrsEnabled === '1';

        return response()->json([
            'success' => true,
            'ifrs_enabled' => $isEnabled,
        ]);
    }

    /**
     * Enable IFRS accounting for a client company.
     * Partners can activate accounting for companies they manage.
     */
    public function enableIfrs(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }

        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $companyModel = Company::find($company);
        if (!$companyModel) {
            return response()->json(['success' => false, 'message' => 'Company not found'], 404);
        }

        CompanySetting::setSettings([
            'ifrs_enabled' => 'YES',
        ], $company);

        return response()->json([
            'success' => true,
            'message' => 'Accounting enabled for this company',
            'ifrs_enabled' => true,
        ]);
    }

    /**
     * VAT Books - Input and Output invoice books with VAT detail.
     *
     * Returns per-invoice VAT breakdown for:
     * - Output book (sales invoices)
     * - Input book (purchase bills/expenses)
     */
    public function vatBooks(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner || !$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        // Validate dates
        if (!$fromDate || !$toDate) {
            return response()->json(['error' => 'Потребни се и почетен и краен датум.', 'message' => 'Потребни се и почетен и краен датум.'], 422);
        }
        if ($fromDate > $toDate) {
            return response()->json(['error' => 'Почетниот датум не може да биде после крајниот.', 'message' => 'Почетниот датум не може да биде после крајниот.'], 422);
        }

        // Output book - Sales invoices
        $invoicesQuery = \App\Models\Invoice::where('company_id', $company)
            ->whereNotIn('status', ['DRAFT'])
            ->with(['customer', 'taxes', 'items.taxes'])
            ->where('invoice_date', '>=', $fromDate)
            ->where('invoice_date', '<=', $toDate);

        $invoices = $invoicesQuery->orderBy('invoice_date')->get();

        $outputEntries = $invoices->map(function ($invoice) {
            $taxTotal = (int) ($invoice->tax ?? 0);
            $subTotal = (int) ($invoice->sub_total ?? ($invoice->total - $taxTotal));
            $vatRate = $this->resolveVatRate($invoice);

            return [
                'id' => $invoice->id,
                'doc_type' => 'invoice',
                'date' => $invoice->invoice_date instanceof \DateTimeInterface ? $invoice->invoice_date->format('Y-m-d') : ($invoice->invoice_date ?? ''),
                'number' => $invoice->invoice_number ?? '',
                'party_name' => $invoice->customer?->name ?? '',
                'party_tax_id' => $invoice->customer?->vat_number ?? $invoice->customer?->tax_id ?? '',
                'total' => (int) ($invoice->total ?? 0),
                'taxable_base' => $subTotal,
                'vat_amount' => $taxTotal,
                'vat_rate' => (float) $vatRate,
            ];
        })->values();

        // Credit notes reduce output VAT (shown as negative entries)
        $creditNotesQuery = \App\Models\CreditNote::where('company_id', $company)
            ->whereNotIn('status', ['DRAFT'])
            ->with(['customer', 'taxes', 'items.taxes'])
            ->where('credit_note_date', '>=', $fromDate)
            ->where('credit_note_date', '<=', $toDate);

        $creditNotes = $creditNotesQuery->orderBy('credit_note_date')->get();

        $creditNoteEntries = $creditNotes->map(function ($cn) {
            $taxTotal = (int) ($cn->tax ?? 0);
            $subTotal = (int) ($cn->sub_total ?? ($cn->total - $taxTotal));
            $vatRate = $this->resolveVatRate($cn);

            return [
                'id' => 'cn_' . $cn->id,
                'doc_type' => 'credit_note',
                'date' => substr((string) ($cn->credit_note_date instanceof \DateTimeInterface ? $cn->credit_note_date->format('Y-m-d') : ($cn->credit_note_date ?? '')), 0, 10),
                'number' => $cn->credit_note_number ?? '',
                'party_name' => $cn->customer?->name ?? '',
                'party_tax_id' => $cn->customer?->vat_number ?? $cn->customer?->tax_id ?? '',
                'total' => -abs((int) ($cn->total ?? 0)),
                'taxable_base' => -abs($subTotal),
                'vat_amount' => -abs($taxTotal),
                'vat_rate' => (float) $vatRate,
            ];
        })->values();

        // Merge invoices + credit notes, sort by date
        $allOutput = $outputEntries->concat($creditNoteEntries)
            ->sortBy('date')
            ->values()
            ->toArray();

        // Input book - Bills (purchases)
        $billsQuery = \App\Models\Bill::where('company_id', $company)
            ->whereNotIn('status', ['DRAFT'])
            ->with(['supplier', 'taxes', 'items.taxes'])
            ->where('bill_date', '>=', $fromDate)
            ->where('bill_date', '<=', $toDate);

        $bills = $billsQuery->orderBy('bill_date')->get();

        $inputEntries = $bills->map(function ($bill) {
            $taxTotal = (int) ($bill->tax ?? 0);
            $subTotal = (int) ($bill->sub_total ?? ($bill->total - $taxTotal));
            $vatRate = $this->resolveVatRate($bill);

            return [
                'id' => $bill->id,
                'doc_type' => 'bill',
                'date' => substr((string) ($bill->bill_date instanceof \DateTimeInterface ? $bill->bill_date->format('Y-m-d') : ($bill->bill_date ?? '')), 0, 10),
                'number' => $bill->bill_number ?? '',
                'party_name' => $bill->supplier?->name ?? '',
                'party_tax_id' => $bill->supplier?->vat_number ?? $bill->supplier?->tax_id ?? '',
                'total' => (int) ($bill->total ?? 0),
                'taxable_base' => $subTotal,
                'vat_amount' => $taxTotal,
                'vat_rate' => (float) $vatRate,
            ];
        })->values()->toArray();

        // Build summary by VAT rate
        $outputByRate = $this->summarizeByRate($allOutput);
        $inputByRate = $this->summarizeByRate($inputEntries);

        return response()->json([
            'success' => true,
            'data' => [
                'output' => $allOutput,
                'input' => $inputEntries,
                'output_by_rate' => $outputByRate,
                'input_by_rate' => $inputByRate,
                'period' => [
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                ],
            ],
        ]);
    }

    /**
     * Export VAT Books as PDF
     */
    public function vatBooksExport(Request $request, int $company): Response
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            abort(404, 'Partner not found');
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            abort(403, 'No access to this company');
        }

        $companyModel = Company::find($company);
        if (!$companyModel) {
            abort(404, 'Company not found');
        }
        $companyModel->load('address');

        $fromDate = $request->query('from_date', now()->startOfMonth()->toDateString());
        $toDate = $request->query('to_date', now()->toDateString());
        $bookType = $request->query('type', 'output'); // output or input

        // Reuse the JSON endpoint logic
        $jsonRequest = new Request(['from_date' => $fromDate, 'to_date' => $toDate]);
        $jsonRequest->setUserResolver(function () use ($request) { return $request->user(); });
        $jsonResponse = $this->vatBooks($jsonRequest, $companyModel->id);
        $data = json_decode($jsonResponse->getContent(), true)['data'] ?? [];

        $entries = $bookType === 'input' ? ($data['input'] ?? []) : ($data['output'] ?? []);

        $currency = \App\Models\Currency::find(
            CompanySetting::getSetting('currency', $company)
        );

        view()->share([
            'company' => $companyModel,
            'entries' => $entries,
            'bookType' => $bookType,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'report_period' => $fromDate . ' - ' . $toDate,
            'currency' => $currency,
        ]);

        $pdf = PDF::loadView('app.pdf.reports.vat-books');
        $typeSlug = $bookType === 'input' ? 'vlezni' : 'izlezni';

        return $pdf->download("kniga_ddv_{$typeSlug}_{$fromDate}_{$toDate}.pdf");
    }

    /**
     * Invoice Register (UJP format) — Книга на влезни/излезни фактури with per-rate columns.
     */
    public function invoiceRegister(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner || !$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        if (!$fromDate || !$toDate) {
            return response()->json(['error' => 'Потребни се и почетен и краен датум.'], 422);
        }

        $service = app(\App\Services\Tax\InvoiceRegisterService::class);

        $output = $service->getOutputRegister($company, $fromDate, $toDate);
        $input = $service->getInputRegister($company, $fromDate, $toDate);

        return response()->json([
            'success' => true,
            'data' => [
                'output' => $output,
                'input' => $input,
                'output_summary' => $service->summarizeByRate($output),
                'input_summary' => $service->summarizeByRate($input),
                'period' => ['from_date' => $fromDate, 'to_date' => $toDate],
            ],
        ]);
    }

    /**
     * Export Invoice Register as UJP-format PDF.
     */
    public function invoiceRegisterExport(Request $request, int $company): Response
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner || !$this->hasCompanyAccess($partner, $company)) {
            abort(403, 'Access denied');
        }

        $companyModel = Company::find($company);
        if (!$companyModel) {
            abort(404, 'Company not found');
        }
        $companyModel->load('address');

        $fromDate = $request->query('from_date', now()->startOfMonth()->toDateString());
        $toDate = $request->query('to_date', now()->toDateString());
        $bookType = $request->query('type', 'output');

        $service = app(\App\Services\Tax\InvoiceRegisterService::class);

        $entries = $bookType === 'input'
            ? $service->getInputRegister($company, $fromDate, $toDate)
            : $service->getOutputRegister($company, $fromDate, $toDate);

        $currency = \App\Models\Currency::find(
            CompanySetting::getSetting('currency', $company)
        );

        $templateName = $bookType === 'input' ? 'kniga-vlezni' : 'kniga-izlezni';

        view()->share([
            'company' => $companyModel,
            'entries' => $entries,
            'bookType' => $bookType,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'currency' => $currency,
            'rates' => \App\Services\Tax\InvoiceRegisterService::rates(),
        ]);

        $pdf = PDF::loadView("app.pdf.reports.ujp.{$templateName}");
        $typeSlug = $bookType === 'input' ? 'vlezni' : 'izlezni';

        return $pdf->download("kniga_{$typeSlug}_fakturi_{$fromDate}_{$toDate}.pdf");
    }

    /**
     * Export General Ledger as PDF
     */
    public function generalLedgerExport(Request $request, int $company): Response
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            abort(404, 'Partner not found');
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            abort(403, 'No access to this company');
        }

        $companyModel = Company::find($company);
        if (!$companyModel) {
            abort(404, 'Company not found');
        }
        $companyModel->load('address');

        $fromDate = $request->query('from_date', now()->startOfYear()->toDateString());
        $toDate = $request->query('to_date', now()->toDateString());
        $accountCode = $request->query('account_code');
        $accountId = $request->query('account_id');

        if (!$accountCode && $accountId) {
            $appAccount = \App\Models\Account::where('company_id', $company)->find($accountId);
            $accountCode = $appAccount?->code;
        }

        if ($accountCode) {
            $ledger = $this->ifrsAdapter->getGeneralLedger($companyModel, null, $fromDate, $toDate, $accountCode);
        } else {
            $ledger = $this->ifrsAdapter->getGeneralLedger($companyModel, (int) $accountId, $fromDate, $toDate);
        }

        $currency = \App\Models\Currency::find(
            CompanySetting::getSetting('currency', $company)
        );

        view()->share([
            'company' => $companyModel,
            'ledger' => $ledger,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'report_period' => $fromDate . ' - ' . $toDate,
            'currency' => $currency,
            'account_name' => $ledger['account_name'] ?? ($accountCode ?? ''),
            'account_code' => $accountCode ?? '',
        ]);

        $pdf = PDF::loadView('app.pdf.reports.general-ledger');
        $slug = $accountCode ?: 'all';

        return $pdf->download("glavna_kniga_{$slug}_{$fromDate}_{$toDate}.pdf");
    }

    /**
     * Export Cash Book as PDF
     */
    public function cashBookExport(Request $request, int $company): Response
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            abort(404, 'Partner not found');
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            abort(403, 'No access to this company');
        }

        $companyModel = Company::find($company);
        if (!$companyModel) {
            abort(404, 'Company not found');
        }
        $companyModel->load('address');

        $fromDate = $request->query('from_date', now()->startOfYear()->toDateString());
        $toDate = $request->query('to_date', now()->toDateString());
        $accountCode = $request->query('account_code', '100');

        $ledger = $this->ifrsAdapter->getGeneralLedger($companyModel, null, $fromDate, $toDate, $accountCode);

        $currency = \App\Models\Currency::find(
            CompanySetting::getSetting('currency', $company)
        );

        view()->share([
            'company' => $companyModel,
            'ledger' => $ledger,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'report_period' => $fromDate . ' - ' . $toDate,
            'currency' => $currency,
            'account_name' => $ledger['account_name'] ?? $accountCode,
            'account_code' => $accountCode,
        ]);

        $pdf = PDF::loadView('app.pdf.reports.cash-book');

        return $pdf->download("kasova_kniga_{$accountCode}_{$fromDate}_{$toDate}.pdf");
    }

    /**
     * Download a single journal entry (налог) as PDF.
     */
    public function journalEntryPdf(Request $request, int $company, int $transaction): Response
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            abort(404, 'Partner not found');
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            abort(403, 'No access to this company');
        }

        $companyModel = Company::find($company);
        if (!$companyModel) {
            abort(404, 'Company not found');
        }
        $companyModel->load('address');

        $entry = $this->ifrsAdapter->getJournalEntry($companyModel, $transaction);
        if (!$entry) {
            abort(404, 'Journal entry not found');
        }

        view()->share([
            'company' => $companyModel,
            'entry' => $entry,
            'report_period' => \Carbon\Carbon::parse($entry['date'])->format('d.m.Y'),
        ]);

        $pdf = PDF::loadView('app.pdf.reports.journal-entry');
        $ref = str_replace('/', '-', $entry['reference'] ?? $transaction);

        return $pdf->download("nalog_{$ref}.pdf");
    }

    /**
     * Reverse (storno) a posted journal entry.
     * Creates a mirror transaction with opposite debit/credit.
     */
    public function reverseJournalEntry(Request $request, int $company, int $transaction): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $companyModel = Company::find($company);
        if (!$companyModel) {
            return response()->json(['success' => false, 'message' => 'Company not found'], 404);
        }

        $reversalId = $this->ifrsAdapter->reverseJournalEntry($companyModel, $transaction);

        if (!$reversalId) {
            return response()->json(['success' => false, 'message' => 'Failed to reverse entry'], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Сторно книжење е креирано',
            'reversal_transaction_id' => $reversalId,
        ]);
    }

    /**
     * Get partner from authenticated request.
     * For super admin, returns a "fake" partner object to allow access.
     */
    protected function getPartnerFromRequest(Request $request): ?Partner
    {
        $user = $request->user();

        if (!$user) {
            return null;
        }

        // Super admin gets a fake partner to pass validation
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
     * Super admin has access to all companies.
     */
    protected function hasCompanyAccess(Partner $partner, int $companyId): bool
    {
        // Super admin has access to all companies
        if ($partner->is_super_admin ?? false) {
            return true;
        }

        return $partner->companies()
            ->where('companies.id', $companyId)
            ->where('partner_company_links.is_active', true)
            ->exists();
    }

    /**
     * Resolve the VAT rate for an invoice or bill.
     * Checks invoice-level taxes first, then item-level taxes.
     * Returns 0 if no taxes found (not a misleading 18% default).
     */
    protected function resolveVatRate($document): float
    {
        // Check document-level taxes first
        if ($document->taxes->isNotEmpty()) {
            // If multiple rates, return the most common one
            $rates = $document->taxes->pluck('percent')->filter()->countBy();
            if ($rates->isNotEmpty()) {
                return (float) $rates->sortDesc()->keys()->first();
            }
        }

        // Check item-level taxes (tax_per_item = YES)
        if ($document->relationLoaded('items') && $document->items->isNotEmpty()) {
            $itemRates = $document->items->flatMap(function ($item) {
                return $item->taxes->pluck('percent');
            })->filter()->countBy();
            if ($itemRates->isNotEmpty()) {
                return (float) $itemRates->sortDesc()->keys()->first();
            }
        }

        // If tax > 0 but no tax relations found, compute from amounts
        $tax = (int) ($document->tax ?? 0);
        $subTotal = (int) ($document->sub_total ?? 0);
        if ($tax > 0 && $subTotal > 0) {
            $computed = round(($tax / $subTotal) * 100, 1);
            // Snap to nearest standard MK rate (18%, 10%, 5%)
            if ($computed >= 16 && $computed <= 20) return 18.0;
            if ($computed >= 8 && $computed <= 12) return 10.0;
            if ($computed >= 4 && $computed <= 6) return 5.0;
            return $computed;
        }

        return 0.0;
    }

    /**
     * Build a summary breakdown by VAT rate.
     */
    protected function summarizeByRate(array $entries): array
    {
        $byRate = [];
        foreach ($entries as $entry) {
            $rate = (string) ($entry['vat_rate'] ?? 0);
            if (!isset($byRate[$rate])) {
                $byRate[$rate] = ['rate' => (float) $rate, 'count' => 0, 'taxable_base' => 0, 'vat_amount' => 0, 'total' => 0];
            }
            $byRate[$rate]['count']++;
            $byRate[$rate]['taxable_base'] += $entry['taxable_base'];
            $byRate[$rate]['vat_amount'] += $entry['vat_amount'];
            $byRate[$rate]['total'] += $entry['total'];
        }
        // Sort by rate descending (18 first, then 5, then 0)
        usort($byRate, fn($a, $b) => $b['rate'] <=> $a['rate']);
        return array_values($byRate);
    }
    /**
     * Trade Book (Трговска книга) — Образец "ET" per Правилник Сл. весник 51/04
     *
     * Chronological evidence of purchase (набавка) and sales (продажба) transactions.
     * Columns per UJP regulation:
     *   1. Реден број
     *   2. Датум на книжење
     *   3. Книговодствен документ (назив и број)
     *   4. Датум на документот
     *   5. Набавна вредност на стоките
     *   6. Продажна вредност на стоките
     *   7. Дневен промет
     */
    public function tradeBook(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner || !$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        if (!$fromDate || !$toDate) {
            return response()->json(['error' => 'Потребни се и почетен и краен датум.', 'message' => 'Потребни се и почетен и краен датум.'], 422);
        }
        if ($fromDate > $toDate) {
            return response()->json(['error' => 'Почетниот датум не може да биде после крајниот.', 'message' => 'Почетниот датум не може да биде после крајниот.'], 422);
        }

        $entries = collect();

        // 1. Sales invoices (излезни фактури) → Набавна = 0, Продажна = total
        $invoices = \App\Models\Invoice::where('company_id', $company)
            ->whereNotIn('status', ['DRAFT'])
            ->with('customer')
            ->where('invoice_date', '>=', $fromDate)
            ->where('invoice_date', '<=', $toDate)
            ->orderBy('invoice_date')
            ->get();

        foreach ($invoices as $inv) {
            $entries->push([
                'date' => substr((string) ($inv->invoice_date instanceof \DateTimeInterface ? $inv->invoice_date->format('Y-m-d') : ($inv->invoice_date ?? '')), 0, 10),
                'doc_name' => 'Фактура',
                'doc_number' => $inv->invoice_number ?? '',
                'doc_date' => substr((string) ($inv->invoice_date instanceof \DateTimeInterface ? $inv->invoice_date->format('Y-m-d') : ($inv->invoice_date ?? '')), 0, 10),
                'party' => $inv->customer?->name ?? '',
                'nabavna' => 0,
                'prodazhna' => (int) ($inv->total ?? 0),
                'promet' => (int) ($inv->total ?? 0),
                'doc_type' => 'invoice',
            ]);
        }

        // 2. Credit notes → negative prodazhna
        $creditNotes = \App\Models\CreditNote::where('company_id', $company)
            ->whereNotIn('status', ['DRAFT'])
            ->with('customer')
            ->where('credit_note_date', '>=', $fromDate)
            ->where('credit_note_date', '<=', $toDate)
            ->orderBy('credit_note_date')
            ->get();

        foreach ($creditNotes as $cn) {
            $entries->push([
                'date' => substr((string) ($cn->credit_note_date instanceof \DateTimeInterface ? $cn->credit_note_date->format('Y-m-d') : ($cn->credit_note_date ?? '')), 0, 10),
                'doc_name' => 'Кредит нота',
                'doc_number' => $cn->credit_note_number ?? '',
                'doc_date' => substr((string) ($cn->credit_note_date instanceof \DateTimeInterface ? $cn->credit_note_date->format('Y-m-d') : ($cn->credit_note_date ?? '')), 0, 10),
                'party' => $cn->customer?->name ?? '',
                'nabavna' => 0,
                'prodazhna' => -abs((int) ($cn->total ?? 0)),
                'promet' => -abs((int) ($cn->total ?? 0)),
                'doc_type' => 'credit_note',
            ]);
        }

        // 3. Bills (влезни фактури) → Набавна = total, Продажна = 0
        $bills = \App\Models\Bill::where('company_id', $company)
            ->whereNotIn('status', ['DRAFT'])
            ->with('supplier')
            ->where('bill_date', '>=', $fromDate)
            ->where('bill_date', '<=', $toDate)
            ->orderBy('bill_date')
            ->get();

        foreach ($bills as $bill) {
            $entries->push([
                'date' => substr((string) ($bill->bill_date instanceof \DateTimeInterface ? $bill->bill_date->format('Y-m-d') : ($bill->bill_date ?? '')), 0, 10),
                'doc_name' => 'Влезна фактура',
                'doc_number' => $bill->bill_number ?? '',
                'doc_date' => substr((string) ($bill->bill_date instanceof \DateTimeInterface ? $bill->bill_date->format('Y-m-d') : ($bill->bill_date ?? '')), 0, 10),
                'party' => $bill->supplier?->name ?? '',
                'nabavna' => (int) ($bill->total ?? 0),
                'prodazhna' => 0,
                'promet' => 0,
                'doc_type' => 'bill',
            ]);
        }

        // 4. Expenses → Набавна = amount, Продажна = 0
        $expenses = \App\Models\Expense::where('company_id', $company)
            ->with('category')
            ->where('expense_date', '>=', $fromDate)
            ->where('expense_date', '<=', $toDate)
            ->orderBy('expense_date')
            ->get();

        foreach ($expenses as $exp) {
            $entries->push([
                'date' => substr((string) ($exp->expense_date instanceof \DateTimeInterface ? $exp->expense_date->format('Y-m-d') : ($exp->expense_date ?? '')), 0, 10),
                'doc_name' => 'Трошок',
                'doc_number' => $exp->expense_number ?? ('EXP-' . $exp->id),
                'doc_date' => substr((string) ($exp->expense_date instanceof \DateTimeInterface ? $exp->expense_date->format('Y-m-d') : ($exp->expense_date ?? '')), 0, 10),
                'party' => $exp->category?->name ?? '',
                'nabavna' => (int) ($exp->amount ?? 0),
                'prodazhna' => 0,
                'promet' => 0,
                'doc_type' => 'expense',
            ]);
        }

        // Sort chronologically, assign sequential numbers
        $sorted = $entries->sortBy('date')->values();

        // Aggregate daily promet (дневен промет)
        $dailyPromet = [];
        foreach ($sorted as $entry) {
            $d = $entry['date'];
            if (!isset($dailyPromet[$d])) {
                $dailyPromet[$d] = 0;
            }
            $dailyPromet[$d] += $entry['promet'];
        }

        $result = [];
        $totalNabavna = 0;
        $totalProdazhna = 0;
        $totalPromet = 0;
        $lastDate = null;

        foreach ($sorted as $i => $entry) {
            $totalNabavna += $entry['nabavna'];
            $totalProdazhna += $entry['prodazhna'];
            $totalPromet += $entry['promet'];

            // Show daily promet only on last entry for each date
            $showDailyPromet = true;
            $remaining = $sorted->slice($i + 1);
            foreach ($remaining as $next) {
                if ($next['date'] === $entry['date']) {
                    $showDailyPromet = false;
                    break;
                }
                break; // only check next entry
            }

            $result[] = [
                'seq' => $i + 1,
                'date' => $entry['date'],
                'doc_name' => $entry['doc_name'],
                'doc_number' => $entry['doc_number'],
                'doc_date' => $entry['doc_date'],
                'party' => $entry['party'],
                'nabavna' => $entry['nabavna'],
                'prodazhna' => $entry['prodazhna'],
                'promet' => $showDailyPromet ? ($dailyPromet[$entry['date']] ?? 0) : null,
                'doc_type' => $entry['doc_type'],
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'entries' => $result,
                'summary' => [
                    'total_nabavna' => $totalNabavna,
                    'total_prodazhna' => $totalProdazhna,
                    'total_promet' => $totalPromet,
                    'count' => count($result),
                ],
                'period' => [
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                ],
            ],
        ]);
    }

    /**
     * Export Trade Book as PDF — Образец "ET"
     */
    public function tradeBookExport(Request $request, int $company): Response
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            abort(404, 'Partner not found');
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            abort(403, 'No access to this company');
        }

        $companyModel = Company::find($company);
        if (!$companyModel) {
            abort(404, 'Company not found');
        }
        $companyModel->load('address');

        $fromDate = $request->query('from_date', now()->startOfMonth()->toDateString());
        $toDate = $request->query('to_date', now()->toDateString());

        // Reuse the JSON endpoint logic
        $jsonRequest = new Request(['from_date' => $fromDate, 'to_date' => $toDate]);
        $jsonRequest->setUserResolver(function () use ($request) { return $request->user(); });
        $jsonResponse = $this->tradeBook($jsonRequest, $companyModel->id);
        $data = json_decode($jsonResponse->getContent(), true)['data'] ?? [];

        $entries = $data['entries'] ?? [];
        $summary = $data['summary'] ?? [];

        $currency = \App\Models\Currency::find(
            CompanySetting::getSetting('currency', $company)
        );

        view()->share([
            'company' => $companyModel,
            'entries' => $entries,
            'summary' => $summary,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'report_period' => $fromDate . ' - ' . $toDate,
            'currency' => $currency,
        ]);

        $pdf = PDF::loadView('app.pdf.reports.trade-book');
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download("trgovska_kniga_{$fromDate}_{$toDate}.pdf");
    }
    /**
     * ПЛТ — Приемен лист во трговијата на мало (Образец ПЛТ)
     * Generates receiving sheet PDF for a specific bill (влезна фактура).
     * 11 columns per Правилник Сл. весник 51/04; 89/04
     */
    public function pltExport(Request $request, int $company, int $billId): Response
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner || !$this->hasCompanyAccess($partner, $company)) {
            abort(403, 'Access denied');
        }

        $companyModel = Company::find($company);
        if (!$companyModel) {
            abort(404, 'Company not found');
        }
        $companyModel->load('address');

        $bill = \App\Models\Bill::where('company_id', $company)
            ->where('id', $billId)
            ->with(['supplier', 'items.item.unit', 'items.taxes.taxType'])
            ->first();

        if (!$bill) {
            abort(404, 'Bill not found');
        }

        $currency = \App\Models\Currency::find(
            CompanySetting::getSetting('currency', $company)
        );

        // Build PLT items from bill items
        $items = [];
        foreach ($bill->items as $billItem) {
            $qty = $billItem->quantity ?? 1;
            $unitPrice = $billItem->price ?? 0; // cents, excl. VAT
            $nabavnaIznos = (int) ($unitPrice * $qty);
            $discount = $billItem->discount_val ?? 0;
            $nabavnaIznos -= $discount;

            // Get VAT info from item taxes
            $vatRate = 0;
            $vatAmount = 0;
            foreach ($billItem->taxes as $tax) {
                $vatRate = $tax->taxType->percent ?? 0;
                $vatAmount += (int) ($tax->amount ?? 0);
            }

            // Продажна = набавна + ДДВ + маржа (approximate: use total)
            $prodazhnaIznos = $nabavnaIznos + $vatAmount;
            $unitPriceProdazhna = $qty > 0 ? (int) round($prodazhnaIznos / $qty) : 0;

            // ДДВ во продажна вредност (пресметковна стапка)
            $prodazhnaVat = $vatAmount;

            $items[] = [
                'name' => $billItem->item?->name ?? $billItem->name ?? '',
                'unit' => $billItem->item?->unit?->name ?? 'ком.',
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'nabavna_iznos' => $nabavnaIznos,
                'vat_amount' => $vatAmount,
                'vat_rate' => $vatRate,
                'unit_price_prodazhna' => $unitPriceProdazhna,
                'prodazhna_iznos' => $prodazhnaIznos,
                'prodazhna_vat' => $prodazhnaVat,
            ];
        }

        $billDate = $bill->bill_date instanceof \DateTimeInterface
            ? $bill->bill_date->format('Y-m-d')
            : ($bill->bill_date ?? '');

        view()->share([
            'company' => $companyModel,
            'bill' => $bill,
            'bill_date' => substr((string) $billDate, 0, 10),
            'supplier_name' => $bill->supplier?->name ?? '',
            'supplier_address' => $bill->supplier?->address_street_1 ?? '',
            'items' => $items,
            'currency' => $currency,
        ]);

        $pdf = PDF::loadView('app.pdf.reports.trade-plt');
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download("plt_{$bill->bill_number}_{$billDate}.pdf");
    }

    /**
     * МЕТГ — Материјална евиденција во трговија на големо (Образец МЕТГ)
     * Generates wholesale material evidence: per-product quantity tracking.
     * 8 columns per Правилник Сл. весник 51/04; 89/04
     */
    public function metgData(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner || !$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $itemId = $request->input('item_id'); // optional: filter by specific item

        if (!$fromDate || !$toDate) {
            return response()->json(['error' => 'Потребни се и почетен и краен датум.'], 422);
        }

        $entries = collect();

        // Inflow (Влез): bill items — purchases
        $billItemsQuery = \App\Models\BillItem::whereHas('bill', function ($q) use ($company, $fromDate, $toDate) {
            $q->where('company_id', $company)
                ->whereNotIn('status', ['DRAFT'])
                ->where('bill_date', '>=', $fromDate)
                ->where('bill_date', '<=', $toDate);
        })->with(['bill.supplier', 'item.unit']);

        if ($itemId) {
            $billItemsQuery->where('item_id', $itemId);
        }

        foreach ($billItemsQuery->get() as $bi) {
            $billDate = $bi->bill->bill_date instanceof \DateTimeInterface
                ? $bi->bill->bill_date->format('Y-m-d')
                : ($bi->bill->bill_date ?? '');

            $entries->push([
                'date' => substr((string) $billDate, 0, 10),
                'doc_number' => $bi->bill->bill_number ?? '',
                'doc_date' => substr((string) $billDate, 0, 10),
                'doc_name' => 'Приемница',
                'party' => $bi->bill->supplier?->name ?? '',
                'item_id' => $bi->item_id,
                'item_name' => $bi->item?->name ?? $bi->name ?? '',
                'unit' => $bi->item?->unit?->name ?? 'ком.',
                'vlez' => (float) ($bi->quantity ?? 0),
                'izlez' => 0,
            ]);
        }

        // Outflow (Излез): invoice items — sales
        $invoiceItemsQuery = \App\Models\InvoiceItem::whereHas('invoice', function ($q) use ($company, $fromDate, $toDate) {
            $q->where('company_id', $company)
                ->whereNotIn('status', ['DRAFT'])
                ->where('invoice_date', '>=', $fromDate)
                ->where('invoice_date', '<=', $toDate);
        })->with(['invoice.customer', 'item.unit']);

        if ($itemId) {
            $invoiceItemsQuery->where('item_id', $itemId);
        }

        foreach ($invoiceItemsQuery->get() as $ii) {
            $invDate = $ii->invoice->invoice_date instanceof \DateTimeInterface
                ? $ii->invoice->invoice_date->format('Y-m-d')
                : ($ii->invoice->invoice_date ?? '');

            $entries->push([
                'date' => substr((string) $invDate, 0, 10),
                'doc_number' => $ii->invoice->invoice_number ?? '',
                'doc_date' => substr((string) $invDate, 0, 10),
                'doc_name' => 'Испратница',
                'party' => $ii->invoice->customer?->name ?? '',
                'item_id' => $ii->item_id,
                'item_name' => $ii->item?->name ?? $ii->name ?? '',
                'unit' => $ii->item?->unit?->name ?? 'ком.',
                'vlez' => 0,
                'izlez' => (float) ($ii->quantity ?? 0),
            ]);
        }

        // Sort chronologically, compute running balance per item
        $sorted = $entries->sortBy('date')->values();

        // Group by item for running balance
        $balanceByItem = [];
        $result = [];

        foreach ($sorted as $i => $entry) {
            $iid = $entry['item_id'] ?? 0;
            if (!isset($balanceByItem[$iid])) {
                $balanceByItem[$iid] = 0;
            }
            $balanceByItem[$iid] += $entry['vlez'] - $entry['izlez'];

            $result[] = [
                'seq' => $i + 1,
                'date' => $entry['date'],
                'doc_number' => $entry['doc_number'],
                'doc_date' => $entry['doc_date'],
                'doc_name' => $entry['doc_name'],
                'party' => $entry['party'],
                'item_name' => $entry['item_name'],
                'unit' => $entry['unit'],
                'vlez' => $entry['vlez'],
                'izlez' => $entry['izlez'],
                'sostojba' => $balanceByItem[$iid],
            ];
        }

        $totalVlez = $sorted->sum('vlez');
        $totalIzlez = $sorted->sum('izlez');

        return response()->json([
            'success' => true,
            'data' => [
                'entries' => $result,
                'summary' => [
                    'total_vlez' => $totalVlez,
                    'total_izlez' => $totalIzlez,
                    'balance' => $totalVlez - $totalIzlez,
                    'count' => count($result),
                ],
            ],
        ]);
    }

    /**
     * МЕТГ PDF Export
     */
    public function metgExport(Request $request, int $company): Response
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner || !$this->hasCompanyAccess($partner, $company)) {
            abort(403, 'Access denied');
        }

        $companyModel = Company::find($company);
        if (!$companyModel) {
            abort(404, 'Company not found');
        }
        $companyModel->load('address');

        $fromDate = $request->query('from_date', now()->startOfYear()->toDateString());
        $toDate = $request->query('to_date', now()->toDateString());

        // Reuse JSON data
        $jsonRequest = new Request(['from_date' => $fromDate, 'to_date' => $toDate, 'item_id' => $request->query('item_id')]);
        $jsonRequest->setUserResolver(function () use ($request) { return $request->user(); });
        $jsonResponse = $this->metgData($jsonRequest, $company);
        $data = json_decode($jsonResponse->getContent(), true)['data'] ?? [];

        $currency = \App\Models\Currency::find(
            CompanySetting::getSetting('currency', $company)
        );

        // Get item info if filtered
        $productName = 'Сите артикли';
        $unitName = 'ком.';
        $warehouseName = '-';
        if ($request->query('item_id')) {
            $item = \App\Models\Item::with('unit')->find($request->query('item_id'));
            if ($item) {
                $productName = $item->name;
                $unitName = $item->unit?->name ?? 'ком.';
            }
        }

        view()->share([
            'company' => $companyModel,
            'entries' => $data['entries'] ?? [],
            'year' => substr($fromDate, 0, 4),
            'product_name' => $productName,
            'warehouse_name' => $warehouseName,
            'unit_name' => $unitName,
            'currency' => $currency,
        ]);

        $pdf = PDF::loadView('app.pdf.reports.trade-metg');
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download("metg_{$fromDate}_{$toDate}.pdf");
    }

    /**
     * ЕТУ — Евиденција за вршење на трговски услуги (Образец ЕТУ)
     * For service-based businesses. Tracks invoiced services with VAT and collection status.
     * 9 columns per Правилник Сл. весник 51/04; 89/04
     */
    public function etuData(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner || !$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        if (!$fromDate || !$toDate) {
            return response()->json(['error' => 'Потребни се и почетен и краен датум.'], 422);
        }

        $entries = collect();

        // Service invoices (services are invoices where items don't have unit "qty" typically)
        $invoices = \App\Models\Invoice::where('company_id', $company)
            ->whereNotIn('status', ['DRAFT'])
            ->with(['customer', 'payments'])
            ->where('invoice_date', '>=', $fromDate)
            ->where('invoice_date', '<=', $toDate)
            ->orderBy('invoice_date')
            ->get();

        foreach ($invoices as $inv) {
            $total = (int) ($inv->total ?? 0);
            $subTotal = (int) ($inv->sub_total ?? 0);
            $taxAmount = $total - $subTotal;
            $collected = (int) $inv->payments->sum('amount');

            $invDate = $inv->invoice_date instanceof \DateTimeInterface
                ? $inv->invoice_date->format('Y-m-d')
                : ($inv->invoice_date ?? '');

            $entries->push([
                'date' => substr((string) $invDate, 0, 10),
                'doc_number' => $inv->invoice_number ?? '',
                'doc_date' => substr((string) $invDate, 0, 10),
                'doc_name' => 'Фактура',
                'party' => $inv->customer?->name ?? '',
                'service_name' => 'Трговски услуги',
                'amount_with_vat' => $total,
                'vat_amount' => $taxAmount,
                'collected' => $collected,
            ]);
        }

        // Sort and assign sequence
        $sorted = $entries->sortBy('date')->values();
        $result = [];
        $totalWithVat = 0;
        $totalVat = 0;
        $totalCollected = 0;

        foreach ($sorted as $i => $entry) {
            $totalWithVat += $entry['amount_with_vat'];
            $totalVat += $entry['vat_amount'];
            $totalCollected += $entry['collected'];

            $result[] = array_merge($entry, ['seq' => $i + 1]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'entries' => $result,
                'summary' => [
                    'total_with_vat' => $totalWithVat,
                    'total_vat' => $totalVat,
                    'total_collected' => $totalCollected,
                    'count' => count($result),
                ],
            ],
        ]);
    }

    /**
     * ЕТУ PDF Export
     */
    public function etuExport(Request $request, int $company): Response
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner || !$this->hasCompanyAccess($partner, $company)) {
            abort(403, 'Access denied');
        }

        $companyModel = Company::find($company);
        if (!$companyModel) {
            abort(404, 'Company not found');
        }
        $companyModel->load('address');

        $fromDate = $request->query('from_date', now()->startOfMonth()->toDateString());
        $toDate = $request->query('to_date', now()->toDateString());

        $jsonRequest = new Request(['from_date' => $fromDate, 'to_date' => $toDate]);
        $jsonRequest->setUserResolver(function () use ($request) { return $request->user(); });
        $jsonResponse = $this->etuData($jsonRequest, $company);
        $data = json_decode($jsonResponse->getContent(), true)['data'] ?? [];

        $currency = \App\Models\Currency::find(
            CompanySetting::getSetting('currency', $company)
        );

        view()->share([
            'company' => $companyModel,
            'entries' => $data['entries'] ?? [],
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'currency' => $currency,
        ]);

        $pdf = PDF::loadView('app.pdf.reports.trade-etu');
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download("etu_{$fromDate}_{$toDate}.pdf");
    }
}
// CLAUDE-CHECKPOINT

