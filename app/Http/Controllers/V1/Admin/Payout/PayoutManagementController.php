<?php

namespace App\Http\Controllers\V1\Admin\Payout;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayoutManagementController extends Controller
{
    /**
     * List payouts with filters and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Payout::query()->with('partner');

        // Search by partner name or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('partner', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payout method
        if ($request->filled('payout_method')) {
            $query->where('payout_method', $request->payout_method);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $allowedSorts = ['created_at', 'amount', 'status', 'payout_date', 'payout_method'];
        if (! in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }
        $sortOrder = $request->get('sort_order', 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $perPage = min((int) $request->get('per_page', 15), 100);
        $payouts = $query->paginate($perPage);

        // Append partner details to each payout
        $payouts->getCollection()->transform(function ($payout) {
            $payout->partner_name = $payout->partner->name ?? '-';
            $payout->partner_email = $payout->partner->email ?? '-';
            $payout->partner_bank_account = $payout->partner->bank_account ?? '-';
            $payout->partner_bank_name = $payout->partner->bank_name ?? '-';

            return $payout;
        });

        return response()->json($payouts);
    }

    /**
     * Show a single payout with partner and events.
     */
    public function show(int $id): JsonResponse
    {
        $payout = Payout::with(['partner', 'processor', 'events.company'])
            ->findOrFail($id);

        $payout->partner_name = $payout->partner->name ?? '-';
        $payout->partner_email = $payout->partner->email ?? '-';
        $payout->partner_bank_account = $payout->partner->bank_account ?? '-';
        $payout->partner_bank_name = $payout->partner->bank_name ?? '-';
        $payout->event_breakdown = $payout->event_breakdown;

        return response()->json($payout);
    }

    /**
     * Payout statistics for dashboard cards.
     */
    public function stats(): JsonResponse
    {
        $pendingAmount = Payout::pending()->sum('amount');
        $pendingCount = Payout::pending()->count();
        $completedThisMonth = Payout::completed()
            ->where('processed_at', '>=', now()->startOfMonth())
            ->sum('amount');
        $totalCompleted = Payout::completed()->sum('amount');

        return response()->json([
            'total_pending_amount' => (float) $pendingAmount,
            'total_pending_count' => $pendingCount,
            'completed_this_month' => (float) $completedThisMonth,
            'total_completed_all_time' => (float) $totalCompleted,
        ]);
    }

    /**
     * Mark a payout as completed (paid via bank transfer).
     */
    public function markCompleted(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'payment_reference' => 'required|string|max:255',
        ]);

        $payout = Payout::findOrFail($id);

        if ($payout->status !== 'pending' && $payout->status !== 'processing') {
            return response()->json([
                'error' => "Cannot mark a {$payout->status} payout as completed.",
            ], 422);
        }

        $payout->markAsCompleted($request->payment_reference, auth()->id());

        return response()->json($payout->fresh('partner'));
    }

    /**
     * Mark a payout as failed.
     */
    public function markFailed(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $payout = Payout::findOrFail($id);

        if (! in_array($payout->status, ['pending', 'processing'])) {
            return response()->json([
                'error' => "Cannot mark a {$payout->status} payout as failed.",
            ], 422);
        }

        $payout->markAsFailed($request->reason);

        return response()->json($payout->fresh('partner'));
    }

    /**
     * Cancel a payout and release events back to unpaid.
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $payout = Payout::findOrFail($id);

        if ($payout->status === 'completed') {
            return response()->json([
                'error' => 'Cannot cancel a completed payout.',
            ], 422);
        }

        $payout->cancel($request->reason);

        return response()->json($payout->fresh('partner'));
    }

    /**
     * Export pending bank transfer payouts as CSV for SEPA upload.
     */
    public function export(Request $request): StreamedResponse
    {
        $payouts = Payout::with('partner')
            ->where('payout_method', 'bank_transfer')
            ->where('status', 'pending')
            ->get();

        return response()->streamDownload(function () use ($payouts) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Partner Name', 'Bank Name', 'Account (IBAN)', 'Amount', 'Currency', 'Payout Date', 'Payout ID']);

            foreach ($payouts as $payout) {
                fputcsv($handle, [
                    $payout->partner->name ?? '-',
                    $payout->partner->bank_name ?? '-',
                    $payout->partner->bank_account ?? '-',
                    $payout->amount,
                    $payout->currency ?? 'MKD',
                    $payout->payout_date?->format('Y-m-d') ?? '-',
                    $payout->id,
                ]);
            }

            fclose($handle);
        }, 'payouts-pending-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
// CLAUDE-CHECKPOINT
