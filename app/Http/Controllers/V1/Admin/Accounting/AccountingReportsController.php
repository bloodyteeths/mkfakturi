<?php

namespace App\Http\Controllers\V1\Admin\Accounting;

use App\Http\Controllers\Controller;
use App\Domain\Accounting\IfrsAdapter;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Accounting Reports Controller
 *
 * Provides API endpoints for IFRS-compliant financial reports:
 * - Trial Balance
 * - Balance Sheet
 * - Income Statement
 *
 * @package App\Http\Controllers\V1\Admin\Accounting
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
     *   path="/api/v1/admin/{company}/accounting/trial-balance",
     *   tags={"Accounting"},
     *   summary="Get trial balance report",
     *   @OA\Parameter(
     *     name="company",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Parameter(
     *     name="as_of_date",
     *     in="query",
     *     required=false,
     *     @OA\Schema(type="string", format="date")
     *   ),
     *   @OA\Response(response=200, description="Trial balance data"),
     *   @OA\Response(response=403, description="Feature disabled"),
     *   @OA\Response(response=404, description="Company not found")
     * )
     *
     * GET /api/v1/admin/{company}/accounting/trial-balance?as_of_date=2025-08-31
     *
     * @param Request $request
     * @param Company $company
     * @return JsonResponse
     */
    public function trialBalance(Request $request, Company $company): JsonResponse
    {
        // Check feature flag
        if (!$this->isFeatureEnabled()) {
            return response()->json([
                'error' => 'Accounting backbone feature is disabled',
                'message' => 'Please enable FEATURE_ACCOUNTING_BACKBONE to access accounting reports'
            ], 403);
        }

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
     *   path="/api/v1/admin/{company}/accounting/balance-sheet",
     *   tags={"Accounting"},
     *   summary="Get balance sheet report",
     *   @OA\Parameter(
     *     name="company",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Parameter(
     *     name="as_of_date",
     *     in="query",
     *     required=false,
     *     @OA\Schema(type="string", format="date")
     *   ),
     *   @OA\Response(response=200, description="Balance sheet data"),
     *   @OA\Response(response=403, description="Feature disabled"),
     *   @OA\Response(response=404, description="Company not found")
     * )
     *
     * GET /api/v1/admin/{company}/accounting/balance-sheet?as_of_date=2025-08-31
     *
     * @param Request $request
     * @param Company $company
     * @return JsonResponse
     */
    public function balanceSheet(Request $request, Company $company): JsonResponse
    {
        // Check feature flag
        if (!$this->isFeatureEnabled()) {
            return response()->json([
                'error' => 'Accounting backbone feature is disabled',
                'message' => 'Please enable FEATURE_ACCOUNTING_BACKBONE to access accounting reports'
            ], 403);
        }

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
     *   path="/api/v1/admin/{company}/accounting/income-statement",
     *   tags={"Accounting"},
     *   summary="Get income statement report",
     *   @OA\Parameter(
     *     name="company",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Parameter(
     *     name="start",
     *     in="query",
     *     required=true,
     *     @OA\Schema(type="string", format="date")
     *   ),
     *   @OA\Parameter(
     *     name="end",
     *     in="query",
     *     required=true,
     *     @OA\Schema(type="string", format="date")
     *   ),
     *   @OA\Response(response=200, description="Income statement data"),
     *   @OA\Response(response=403, description="Feature disabled"),
     *   @OA\Response(response=404, description="Company not found"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     *
     * GET /api/v1/admin/{company}/accounting/income-statement?start=2025-01-01&end=2025-08-31
     *
     * @param Request $request
     * @param Company $company
     * @return JsonResponse
     */
    public function incomeStatement(Request $request, Company $company): JsonResponse
    {
        // Check feature flag
        if (!$this->isFeatureEnabled()) {
            return response()->json([
                'error' => 'Accounting backbone feature is disabled',
                'message' => 'Please enable FEATURE_ACCOUNTING_BACKBONE to access accounting reports'
            ], 403);
        }

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
     * Check if accounting backbone feature is enabled
     *
     * @return bool
     */
    protected function isFeatureEnabled(): bool
    {
        // Check Laravel Pennant feature flag or config
        if (function_exists('feature')) {
            return feature('accounting_backbone');
        }

        return config('ifrs.enabled', false) ||
               env('FEATURE_ACCOUNTING_BACKBONE', false);
    }
}

// CLAUDE-CHECKPOINT
