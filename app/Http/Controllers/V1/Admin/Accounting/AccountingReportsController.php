<?php

namespace App\Http\Controllers\V1\Admin\Accounting;

use App\Domain\Accounting\IfrsAdapter;
use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Accounting Reports Controller
 *
 * Provides API endpoints for IFRS-compliant financial reports:
 * - Trial Balance
 * - Balance Sheet
 * - Income Statement
 */
class AccountingReportsController extends Controller
{
    protected IfrsAdapter $ifrsAdapter;

    public function __construct(IfrsAdapter $ifrsAdapter)
    {
        $this->ifrsAdapter = $ifrsAdapter;
    }

    /**
     * Get Trial Balance Report
     *
     * @OA\Get(
     *   path="/api/v1/accounting/trial-balance",
     *   tags={"Accounting"},
     *   summary="Get trial balance report",
     *
     *   @OA\Parameter(
     *     name="as_of_date",
     *     in="query",
     *     required=false,
     *
     *     @OA\Schema(type="string", format="date")
     *   ),
     *
     *   @OA\Response(response=200, description="Trial balance data"),
     *   @OA\Response(response=403, description="Feature disabled"),
     *   @OA\Response(response=404, description="Company not found")
     * )
     *
     * GET /api/v1/accounting/trial-balance?as_of_date=2025-08-31
     */
    public function trialBalance(Request $request): JsonResponse
    {
        // Check feature flag
        if (! $this->isFeatureEnabled()) {
            return response()->json([
                'error' => 'Accounting backbone feature is disabled',
                'message' => 'Please enable FEATURE_ACCOUNTING_BACKBONE to access accounting reports',
            ], 403);
        }

        // Get company from header (set by company middleware)
        $companyId = $request->header('company');

        if (! $companyId) {
            return response()->json([
                'error' => 'Missing company header',
                'message' => 'The company header is required for accounting reports',
            ], 400);
        }

        $company = Company::find($companyId);

        if (! $company) {
            return response()->json([
                'error' => 'Company not found',
                'message' => 'The specified company does not exist',
            ], 404);
        }
        // CLAUDE-CHECKPOINT

        // Authorize user can access this company
        $this->authorize('view', $company);

        $asOfDate = $request->query('as_of_date', now()->toDateString());

        $trialBalance = $this->ifrsAdapter->getTrialBalance($company, $asOfDate);

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
     *
     * @OA\Get(
     *   path="/api/v1/accounting/balance-sheet",
     *   tags={"Accounting"},
     *   summary="Get balance sheet report",
     *
     *   @OA\Parameter(
     *     name="as_of_date",
     *     in="query",
     *     required=false,
     *
     *     @OA\Schema(type="string", format="date")
     *   ),
     *
     *   @OA\Response(response=200, description="Balance sheet data"),
     *   @OA\Response(response=403, description="Feature disabled"),
     *   @OA\Response(response=404, description="Company not found")
     * )
     *
     * GET /api/v1/accounting/balance-sheet?as_of_date=2025-08-31
     */
    public function balanceSheet(Request $request): JsonResponse
    {
        // Check feature flag
        if (! $this->isFeatureEnabled()) {
            return response()->json([
                'error' => 'Accounting backbone feature is disabled',
                'message' => 'Please enable FEATURE_ACCOUNTING_BACKBONE to access accounting reports',
            ], 403);
        }

        // Get company from header (set by company middleware)
        $companyId = $request->header('company');

        if (! $companyId) {
            return response()->json([
                'error' => 'Missing company header',
                'message' => 'The company header is required for accounting reports',
            ], 400);
        }

        $company = Company::find($companyId);

        if (! $company) {
            return response()->json([
                'error' => 'Company not found',
                'message' => 'The specified company does not exist',
            ], 404);
        }
        // CLAUDE-CHECKPOINT

        // Authorize user can access this company
        $this->authorize('view', $company);

        $asOfDate = $request->query('as_of_date', now()->toDateString());

        $balanceSheet = $this->ifrsAdapter->getBalanceSheet($company, $asOfDate);

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
     *
     * @OA\Get(
     *   path="/api/v1/accounting/income-statement",
     *   tags={"Accounting"},
     *   summary="Get income statement report",
     *
     *   @OA\Parameter(
     *     name="start",
     *     in="query",
     *     required=true,
     *
     *     @OA\Schema(type="string", format="date")
     *   ),
     *
     *   @OA\Parameter(
     *     name="end",
     *     in="query",
     *     required=true,
     *
     *     @OA\Schema(type="string", format="date")
     *   ),
     *
     *   @OA\Response(response=200, description="Income statement data"),
     *   @OA\Response(response=403, description="Feature disabled"),
     *   @OA\Response(response=404, description="Company not found"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     *
     * GET /api/v1/accounting/income-statement?start=2025-01-01&end=2025-08-31
     */
    public function incomeStatement(Request $request): JsonResponse
    {
        // Check feature flag
        if (! $this->isFeatureEnabled()) {
            return response()->json([
                'error' => 'Accounting backbone feature is disabled',
                'message' => 'Please enable FEATURE_ACCOUNTING_BACKBONE to access accounting reports',
            ], 403);
        }

        // Get company from header (set by company middleware)
        $companyId = $request->header('company');

        if (! $companyId) {
            return response()->json([
                'error' => 'Missing company header',
                'message' => 'The company header is required for accounting reports',
            ], 400);
        }

        $company = Company::find($companyId);

        if (! $company) {
            return response()->json([
                'error' => 'Company not found',
                'message' => 'The specified company does not exist',
            ], 404);
        }
        // CLAUDE-CHECKPOINT

        // Authorize user can access this company
        $this->authorize('view', $company);

        // Validate date parameters
        $validated = $request->validate([
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
        ]);

        $incomeStatement = $this->ifrsAdapter->getIncomeStatement(
            $company,
            $validated['start'],
            $validated['end']
        );

        if (isset($incomeStatement['error'])) {
            return response()->json($incomeStatement, 400);
        }

        return response()->json([
            'success' => true,
            'income_statement' => $incomeStatement,
        ]);
    }

    /**
     * Backfill existing invoices to the ledger
     *
     * POST /api/v1/accounting/backfill-invoices?dry_run=1
     */
    public function backfillInvoices(Request $request): JsonResponse
    {
        // Check feature flag
        if (! $this->isFeatureEnabled()) {
            return response()->json([
                'error' => 'Accounting backbone feature is disabled',
                'message' => 'Please enable FEATURE_ACCOUNTING_BACKBONE to backfill invoices',
            ], 403);
        }

        // Get company from header
        $companyId = $request->header('company');

        if (! $companyId) {
            return response()->json([
                'error' => 'Missing company header',
                'message' => 'The company header is required',
            ], 400);
        }

        $company = Company::find($companyId);

        if (! $company) {
            return response()->json([
                'error' => 'Company not found',
            ], 404);
        }

        // Authorize user can manage this company
        $this->authorize('manage', $company);

        $dryRun = $request->boolean('dry_run', false);
        $stats = $this->ifrsAdapter->backfillInvoices($company, $dryRun);

        if (isset($stats['error'])) {
            return response()->json($stats, 400);
        }

        return response()->json([
            'success' => true,
            'dry_run' => $dryRun,
            'message' => $dryRun
                ? "Found {$stats['posted']} invoices to post"
                : "Posted {$stats['posted']} invoices, {$stats['failed']} failed",
            'stats' => $stats,
        ]);
    }

    /**
     * Get General Ledger Report
     *
     * @OA\Get(
     *   path="/api/v1/accounting/general-ledger",
     *   tags={"Accounting"},
     *   summary="Get general ledger report for a specific account",
     *
     *   @OA\Parameter(
     *     name="account_id",
     *     in="query",
     *     required=true,
     *
     *     @OA\Schema(type="integer")
     *   ),
     *
     *   @OA\Parameter(
     *     name="start_date",
     *     in="query",
     *     required=true,
     *
     *     @OA\Schema(type="string", format="date")
     *   ),
     *
     *   @OA\Parameter(
     *     name="end_date",
     *     in="query",
     *     required=true,
     *
     *     @OA\Schema(type="string", format="date")
     *   ),
     *
     *   @OA\Response(response=200, description="General ledger data"),
     *   @OA\Response(response=403, description="Feature disabled"),
     *   @OA\Response(response=404, description="Company not found"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     *
     * GET /api/v1/accounting/general-ledger?account_id=1&start_date=2025-01-01&end_date=2025-12-31
     */
    public function generalLedger(Request $request): JsonResponse
    {
        // Check feature flag
        if (! $this->isFeatureEnabled()) {
            return response()->json([
                'error' => 'Accounting backbone feature is disabled',
                'message' => 'Please enable FEATURE_ACCOUNTING_BACKBONE to access accounting reports',
            ], 403);
        }

        // Get company from header (set by company middleware)
        $companyId = $request->header('company');

        if (! $companyId) {
            return response()->json([
                'error' => 'Missing company header',
                'message' => 'The company header is required for accounting reports',
            ], 400);
        }

        $company = Company::find($companyId);

        if (! $company) {
            return response()->json([
                'error' => 'Company not found',
                'message' => 'The specified company does not exist',
            ], 404);
        }
        // CLAUDE-CHECKPOINT

        // Authorize user can access this company
        $this->authorize('view', $company);

        // Validate parameters - accept either account_id (IFRS ID) or account_code
        $validated = $request->validate([
            'account_id' => 'required_without:account_code|integer',
            'account_code' => 'required_without:account_id|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $generalLedger = $this->ifrsAdapter->getGeneralLedger(
            $company,
            $validated['account_id'] ?? null,
            $validated['start_date'],
            $validated['end_date'],
            $validated['account_code'] ?? null
        );

        if (isset($generalLedger['error'])) {
            return response()->json($generalLedger, 400);
        }

        return response()->json([
            'success' => true,
            'data' => $generalLedger,
        ]);
    }

    /**
     * Get Journal Entries Report
     *
     * @OA\Get(
     *   path="/api/v1/accounting/journal-entries",
     *   tags={"Accounting"},
     *   summary="Get journal entries report",
     *
     *   @OA\Parameter(
     *     name="start_date",
     *     in="query",
     *     required=true,
     *
     *     @OA\Schema(type="string", format="date")
     *   ),
     *
     *   @OA\Parameter(
     *     name="end_date",
     *     in="query",
     *     required=true,
     *
     *     @OA\Schema(type="string", format="date")
     *   ),
     *
     *   @OA\Parameter(
     *     name="page",
     *     in="query",
     *     required=false,
     *
     *     @OA\Schema(type="integer")
     *   ),
     *
     *   @OA\Parameter(
     *     name="per_page",
     *     in="query",
     *     required=false,
     *
     *     @OA\Schema(type="integer")
     *   ),
     *
     *   @OA\Response(response=200, description="Journal entries data"),
     *   @OA\Response(response=403, description="Feature disabled"),
     *   @OA\Response(response=404, description="Company not found"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     *
     * GET /api/v1/accounting/journal-entries?start_date=2025-01-01&end_date=2025-12-31
     */
    public function journalEntries(Request $request): JsonResponse
    {
        // Check feature flag
        if (! $this->isFeatureEnabled()) {
            return response()->json([
                'error' => 'Accounting backbone feature is disabled',
                'message' => 'Please enable FEATURE_ACCOUNTING_BACKBONE to access accounting reports',
            ], 403);
        }

        // Get company from header (set by company middleware)
        $companyId = $request->header('company');

        if (! $companyId) {
            return response()->json([
                'error' => 'Missing company header',
                'message' => 'The company header is required for accounting reports',
            ], 400);
        }

        $company = Company::find($companyId);

        if (! $company) {
            return response()->json([
                'error' => 'Company not found',
                'message' => 'The specified company does not exist',
            ], 404);
        }
        // CLAUDE-CHECKPOINT

        // Authorize user can access this company
        $this->authorize('view', $company);

        // Validate parameters
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $journalEntries = $this->ifrsAdapter->getJournalEntries(
            $company,
            $validated['start_date'],
            $validated['end_date']
        );

        if (isset($journalEntries['error'])) {
            return response()->json($journalEntries, 400);
        }

        // Apply pagination if requested
        $page = $validated['page'] ?? 1;
        $perPage = $validated['per_page'] ?? 25;
        $entries = $journalEntries['entries'];
        $total = count($entries);
        $offset = ($page - 1) * $perPage;
        $paginatedEntries = array_slice($entries, $offset, $perPage);

        return response()->json([
            'success' => true,
            'data' => $paginatedEntries,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
                'from' => $offset + 1,
                'to' => min($offset + $perPage, $total),
            ],
        ]);
    }

    /**
     * Check if accounting backbone feature is enabled
     */
    protected function isFeatureEnabled(): bool
    {
        // Check Laravel Pennant feature flag or config
        if (function_exists('feature')) {
            return feature('accounting-backbone');
        }

        return config('ifrs.enabled', false) ||
               env('FEATURE_ACCOUNTING_BACKBONE', false);
    }
}

// CLAUDE-CHECKPOINT
