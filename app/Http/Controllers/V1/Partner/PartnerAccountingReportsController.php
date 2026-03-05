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
     * Get Trial Balance Report
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

        $asOfDate = $request->query('as_of_date', now()->toDateString());

        $trialBalance = $this->ifrsAdapter->getTrialBalance($companyModel, $asOfDate);

        if (isset($trialBalance['error'])) {
            return response()->json($trialBalance, 400);
        }

        return response()->json([
            'success' => true,
            'trial_balance' => $trialBalance,
        ]);
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
    public function vatBooks(Request $request, Company $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner || !$this->hasCompanyAccess($partner, $company->id)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        // Output book - Sales invoices
        $invoicesQuery = \App\Models\Invoice::where('company_id', $company->id)
            ->whereNotIn('status', ['DRAFT'])
            ->with(['customer', 'taxes']);

        if ($fromDate) {
            $invoicesQuery->where('invoice_date', '>=', $fromDate);
        }
        if ($toDate) {
            $invoicesQuery->where('invoice_date', '<=', $toDate);
        }

        $invoices = $invoicesQuery->orderBy('invoice_date')->get();

        $outputEntries = $invoices->map(function ($invoice) {
            $taxTotal = $invoice->taxes->sum('amount') ?? $invoice->tax_total ?? 0;
            $subTotal = $invoice->sub_total ?? ($invoice->total - $taxTotal);
            $vatRate = $invoice->taxes->first()?->percent ?? 18;

            return [
                'id' => $invoice->id,
                'date' => $invoice->invoice_date?->format('Y-m-d') ?? '',
                'number' => $invoice->invoice_number ?? '',
                'party_name' => $invoice->customer?->name ?? '',
                'party_tax_id' => $invoice->customer?->tax_identification_number ?? '',
                'total' => (int) ($invoice->total ?? 0),
                'taxable_base' => (int) $subTotal,
                'vat_amount' => (int) $taxTotal,
                'vat_rate' => (float) $vatRate,
            ];
        })->values()->toArray();

        // Input book - Bills (purchases)
        $billsQuery = \App\Models\Bill::where('company_id', $company->id)
            ->whereNotIn('status', ['DRAFT'])
            ->with(['supplier', 'taxes']);

        if ($fromDate) {
            $billsQuery->where('bill_date', '>=', $fromDate);
        }
        if ($toDate) {
            $billsQuery->where('bill_date', '<=', $toDate);
        }

        $bills = $billsQuery->orderBy('bill_date')->get();

        $inputEntries = $bills->map(function ($bill) {
            $taxTotal = $bill->taxes->sum('amount') ?? $bill->tax_total ?? 0;
            $subTotal = $bill->sub_total ?? ($bill->total - $taxTotal);
            $vatRate = $bill->taxes->first()?->percent ?? 18;

            return [
                'id' => $bill->id,
                'date' => $bill->bill_date?->format('Y-m-d') ?? '',
                'number' => $bill->bill_number ?? '',
                'party_name' => $bill->supplier?->name ?? '',
                'party_tax_id' => $bill->supplier?->tax_identification_number ?? '',
                'total' => (int) ($bill->total ?? 0),
                'taxable_base' => (int) $subTotal,
                'vat_amount' => (int) $taxTotal,
                'vat_rate' => (float) $vatRate,
            ];
        })->values()->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'output' => $outputEntries,
                'input' => $inputEntries,
                'period' => [
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                ],
            ],
        ]);
    }

    // CLAUDE-CHECKPOINT

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
}

