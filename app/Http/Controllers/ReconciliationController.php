<?php

namespace App\Http\Controllers;

use App\Models\BankTransaction;
use App\Models\Invoice;
use App\Models\Reconciliation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ReconciliationController
 *
 * Manages invoice-bank transaction reconciliation with confidence scoring.
 * Supports auto-matched, suggested, and manual reconciliation workflows.
 */
class ReconciliationController extends Controller
{
    /**
     * Get auto-matched reconciliations (confidence ≥ 0.9)
     *
     * GET /api/v1/{company}/reconciliation/auto-matched
     */
    public function autoMatched(Request $request): JsonResponse
    {
        $companyId = $request->header('company');

        $reconciliations = Reconciliation::whereCompany($companyId)
            ->autoMatched()
            ->with(['bankTransaction', 'invoice', 'reconciledBy'])
            ->orderBy('reconciled_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $reconciliations,
            'threshold' => Reconciliation::THRESHOLD_AUTO_MATCH,
        ]);
    }

    /**
     * Get suggested matches requiring approval (0.5 ≤ confidence < 0.9)
     *
     * GET /api/v1/{company}/reconciliation/suggested
     */
    public function suggested(Request $request): JsonResponse
    {
        $companyId = $request->header('company');

        $reconciliations = Reconciliation::whereCompany($companyId)
            ->suggested()
            ->with(['bankTransaction', 'invoice'])
            ->orderBy('confidence_score', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $reconciliations,
            'threshold_min' => Reconciliation::THRESHOLD_SUGGEST,
            'threshold_max' => Reconciliation::THRESHOLD_AUTO_MATCH,
        ]);
    }

    /**
     * Get manual reconciliation candidates (confidence < 0.5)
     *
     * GET /api/v1/{company}/reconciliation/manual
     */
    public function manual(Request $request): JsonResponse
    {
        $companyId = $request->header('company');

        $reconciliations = Reconciliation::whereCompany($companyId)
            ->manual()
            ->with(['bankTransaction', 'invoice'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Also get unmatched transactions for manual linking
        $unmatchedTransactions = BankTransaction::forCompany($companyId)
            ->unmatched()
            ->credits() // Only incoming payments
            ->recent(90) // Last 90 days
            ->orderBy('transaction_date', 'desc')
            ->limit(50)
            ->get();

        // Get unpaid invoices for manual linking
        $unpaidInvoices = Invoice::where('company_id', $companyId)
            ->whereIn('paid_status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID])
            ->orderBy('due_date', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'reconciliations' => $reconciliations,
                'unmatched_transactions' => $unmatchedTransactions,
                'unpaid_invoices' => $unpaidInvoices,
            ],
            'threshold' => Reconciliation::THRESHOLD_SUGGEST,
        ]);
    }

    /**
     * Approve a suggested reconciliation match
     *
     * POST /api/v1/{company}/reconciliation/approve
     */
    public function approve(Request $request): JsonResponse
    {
        $request->validate([
            'reconciliation_id' => 'required|exists:reconciliations,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $companyId = $request->header('company');
        $reconciliation = Reconciliation::whereCompany($companyId)
            ->findOrFail($request->reconciliation_id);

        // Check if already approved or rejected
        if ($reconciliation->status !== Reconciliation::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'This reconciliation has already been '.$reconciliation->status,
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Approve the reconciliation
            $reconciliation->approve(auth()->id(), $request->notes);

            // Mark bank transaction as matched
            $reconciliation->bankTransaction->markAsMatched(
                $reconciliation->invoice_id,
                null, // Payment will be created separately if needed
                $reconciliation->confidence_score
            );

            // Log the approval
            Log::info('Reconciliation approved', [
                'reconciliation_id' => $reconciliation->id,
                'bank_transaction_id' => $reconciliation->bank_transaction_id,
                'invoice_id' => $reconciliation->invoice_id,
                'confidence_score' => $reconciliation->confidence_score,
                'approved_by' => auth()->id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Reconciliation approved successfully',
                'data' => $reconciliation->fresh(['bankTransaction', 'invoice', 'reconciledBy']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to approve reconciliation', [
                'reconciliation_id' => $reconciliation->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to approve reconciliation: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject a suggested reconciliation match
     *
     * POST /api/v1/{company}/reconciliation/reject
     */
    public function reject(Request $request): JsonResponse
    {
        $request->validate([
            'reconciliation_id' => 'required|exists:reconciliations,id',
            'reason' => 'required|string|max:1000',
        ]);

        $companyId = $request->header('company');
        $reconciliation = Reconciliation::whereCompany($companyId)
            ->findOrFail($request->reconciliation_id);

        // Check if already approved or rejected
        if ($reconciliation->status !== Reconciliation::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'This reconciliation has already been '.$reconciliation->status,
            ], 400);
        }

        try {
            // Reject the reconciliation
            $reconciliation->reject($request->reason, auth()->id());

            // Log the rejection
            Log::info('Reconciliation rejected', [
                'reconciliation_id' => $reconciliation->id,
                'bank_transaction_id' => $reconciliation->bank_transaction_id,
                'invoice_id' => $reconciliation->invoice_id,
                'reason' => $request->reason,
                'rejected_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reconciliation rejected successfully',
                'data' => $reconciliation->fresh(['bankTransaction', 'invoice', 'reconciledBy']),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to reject reconciliation', [
                'reconciliation_id' => $reconciliation->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reject reconciliation: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get reconciliation statistics for the company
     *
     * GET /api/v1/{company}/reconciliation/stats
     */
    public function stats(Request $request): JsonResponse
    {
        $companyId = $request->header('company');

        $stats = [
            'auto_matched' => Reconciliation::whereCompany($companyId)->autoMatched()->count(),
            'suggested' => Reconciliation::whereCompany($companyId)->suggested()->count(),
            'manual' => Reconciliation::whereCompany($companyId)->manual()->count(),
            'approved' => Reconciliation::whereCompany($companyId)->whereStatus(Reconciliation::STATUS_APPROVED)->count(),
            'rejected' => Reconciliation::whereCompany($companyId)->whereStatus(Reconciliation::STATUS_REJECTED)->count(),
            'total' => Reconciliation::whereCompany($companyId)->count(),
        ];

        // Get unmatched transactions count
        $stats['unmatched_transactions'] = BankTransaction::forCompany($companyId)
            ->unmatched()
            ->count();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}

// CLAUDE-CHECKPOINT
