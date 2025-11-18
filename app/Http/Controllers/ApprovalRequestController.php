<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * ApprovalRequestController
 *
 * Manages document approval workflow.
 * Supports Invoice, Estimate, Expense, Bill, CreditNote approval process.
 */
class ApprovalRequestController extends Controller
{
    /**
     * Get all pending approval requests for the company
     *
     * GET /api/v1/{company}/approvals
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->header('company');

        $approvals = ApprovalRequest::whereCompany($companyId)
            ->with(['approvable', 'requestedBy', 'approvedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $approvals,
        ]);
    }

    /**
     * Get pending approval requests only
     *
     * GET /api/v1/{company}/approvals/pending
     */
    public function pending(Request $request): JsonResponse
    {
        $companyId = $request->header('company');

        $approvals = ApprovalRequest::whereCompany($companyId)
            ->wherePending()
            ->with(['approvable', 'requestedBy'])
            ->orderBy('created_at', 'asc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $approvals,
        ]);
    }

    /**
     * Get approval history for a specific document
     *
     * GET /api/v1/{company}/approvals/document/{type}/{id}
     *
     * @param  string  $type  Document type (invoice, estimate, expense, bill, credit-note)
     * @param  int  $id  Document ID
     */
    public function documentHistory(Request $request, string $type, int $id): JsonResponse
    {
        $companyId = $request->header('company');

        // Map URL type to model class
        $modelClass = match ($type) {
            'invoice' => 'App\\Models\\Invoice',
            'estimate' => 'App\\Models\\Estimate',
            'expense' => 'App\\Models\\Expense',
            'bill' => 'App\\Models\\Bill',
            'credit-note' => 'App\\Models\\CreditNote',
            default => null,
        };

        if (! $modelClass) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid document type',
            ], 400);
        }

        $approvals = ApprovalRequest::whereCompany($companyId)
            ->forDocument($modelClass, $id)
            ->with(['requestedBy', 'approvedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $approvals,
        ]);
    }

    /**
     * Approve an approval request
     *
     * POST /api/v1/{company}/approvals/{id}/approve
     *
     * @param  int  $id  Approval request ID
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'note' => 'nullable|string|max:1000',
        ]);

        $companyId = $request->header('company');
        $approval = ApprovalRequest::whereCompany($companyId)->findOrFail($id);

        // Check if already processed
        if (! $approval->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'This approval request has already been '.$approval->status,
            ], 400);
        }

        // Check if user is trying to approve their own request
        if ($approval->requested_by === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot approve your own request',
            ], 403);
        }

        // Authorize
        $this->authorize('approve', $approval);

        try {
            $approval->approve(auth()->id(), $request->note);

            Log::info('Document approval approved', [
                'approval_id' => $approval->id,
                'document_type' => $approval->approvable_type,
                'document_id' => $approval->approvable_id,
                'approved_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document approved successfully',
                'data' => $approval->fresh(['approvable', 'requestedBy', 'approvedBy']),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to approve document', [
                'approval_id' => $approval->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to approve document: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject an approval request
     *
     * POST /api/v1/{company}/approvals/{id}/reject
     *
     * @param  int  $id  Approval request ID
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'note' => 'required|string|max:1000',
        ]);

        $companyId = $request->header('company');
        $approval = ApprovalRequest::whereCompany($companyId)->findOrFail($id);

        // Check if already processed
        if (! $approval->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'This approval request has already been '.$approval->status,
            ], 400);
        }

        // Authorize
        $this->authorize('reject', $approval);

        try {
            $approval->reject($request->note, auth()->id());

            Log::info('Document approval rejected', [
                'approval_id' => $approval->id,
                'document_type' => $approval->approvable_type,
                'document_id' => $approval->approvable_id,
                'rejected_by' => auth()->id(),
                'reason' => $request->note,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document rejected successfully',
                'data' => $approval->fresh(['approvable', 'requestedBy', 'approvedBy']),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to reject document', [
                'approval_id' => $approval->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reject document: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get approval statistics
     *
     * GET /api/v1/{company}/approvals/stats
     */
    public function stats(Request $request): JsonResponse
    {
        $companyId = $request->header('company');

        $stats = [
            'pending' => ApprovalRequest::whereCompany($companyId)->wherePending()->count(),
            'approved' => ApprovalRequest::whereCompany($companyId)->whereStatus(ApprovalRequest::STATUS_APPROVED)->count(),
            'rejected' => ApprovalRequest::whereCompany($companyId)->whereStatus(ApprovalRequest::STATUS_REJECTED)->count(),
            'total' => ApprovalRequest::whereCompany($companyId)->count(),
        ];

        // Stats by document type
        $byType = ApprovalRequest::whereCompany($companyId)
            ->selectRaw('approvable_type, COUNT(*) as count')
            ->groupBy('approvable_type')
            ->get()
            ->mapWithKeys(function ($item) {
                $typeName = match ($item->approvable_type) {
                    'App\\Models\\Invoice' => 'invoices',
                    'App\\Models\\Estimate' => 'estimates',
                    'App\\Models\\Expense' => 'expenses',
                    'App\\Models\\Bill' => 'bills',
                    'App\\Models\\CreditNote' => 'credit_notes',
                    default => 'other',
                };

                return [$typeName => $item->count];
            });

        $stats['by_type'] = $byType;

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}

// CLAUDE-CHECKPOINT
