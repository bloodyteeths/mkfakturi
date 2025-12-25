<?php

namespace App\Http\Controllers\V1\Partner;

use App\Domain\Accounting\IfrsAdapter;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Partner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $ledger = $this->ifrsAdapter->getGeneralLedger($companyModel, $fromDate, $toDate, $accountId);

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
     * Get partner from authenticated request.
     */
    protected function getPartnerFromRequest(Request $request): ?Partner
    {
        $user = $request->user();

        if (!$user) {
            return null;
        }

        return Partner::where('user_id', $user->id)->first();
    }

    /**
     * Check if partner has access to a company.
     */
    protected function hasCompanyAccess(Partner $partner, int $companyId): bool
    {
        return $partner->companies()
            ->where('companies.id', $companyId)
            ->where('partner_company_links.is_active', true)
            ->exists();
    }
}

// CLAUDE-CHECKPOINT
