<?php

namespace App\Http\Controllers\V1\Admin\Accounting;

use App\Http\Controllers\Controller;
use App\Models\DailyClosing;
use App\Models\PeriodLock;
use App\Services\PeriodLockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Period Lock Controller
 *
 * Manages daily closings and period locks.
 * Part of Phase 3: Daily Closing & Period Lock.
 */
class PeriodLockController extends Controller
{
    protected PeriodLockService $lockService;

    public function __construct(PeriodLockService $lockService)
    {
        $this->lockService = $lockService;
    }

    /**
     * List all daily closings for the company.
     */
    public function indexDailyClosings(Request $request): JsonResponse
    {
        $this->authorize('view-financial-reports');

        $companyId = $request->header('company');

        $closings = $this->lockService->getClosedDays(
            $companyId,
            $request->from_date,
            $request->to_date
        );

        return response()->json([
            'success' => true,
            'data' => $closings,
        ]);
    }

    /**
     * Create a daily closing.
     */
    public function storeDailyClosing(Request $request): JsonResponse
    {
        $this->authorize('manage-closings');

        $request->validate([
            'date' => 'required|date',
            'type' => 'nullable|string|in:all,cash,invoices',
            'notes' => 'nullable|string|max:1000',
        ]);

        $companyId = $request->header('company');
        $type = $request->type ?? DailyClosing::TYPE_ALL;

        // Check if already closed
        if (DailyClosing::isDateClosed($companyId, $request->date, $type)) {
            return response()->json([
                'success' => false,
                'message' => 'This date is already closed.',
            ], 422);
        }

        $closing = $this->lockService->closeDay(
            $companyId,
            $request->date,
            $type,
            auth()->id(),
            $request->notes
        );

        return response()->json([
            'success' => true,
            'message' => 'Day closed successfully.',
            'data' => $closing->load('closedBy'),
        ], 201);
    }

    /**
     * Delete a daily closing.
     */
    public function destroyDailyClosing(Request $request, int $id): JsonResponse
    {
        $this->authorize('manage-closings');

        $companyId = $request->header('company');

        $closing = DailyClosing::where('company_id', $companyId)
            ->findOrFail($id);

        $closing->delete();

        return response()->json([
            'success' => true,
            'message' => 'Day unlocked successfully.',
        ]);
    }

    /**
     * List all period locks for the company.
     */
    public function indexPeriodLocks(Request $request): JsonResponse
    {
        $this->authorize('view-financial-reports');

        $companyId = $request->header('company');

        $locks = $this->lockService->getPeriodLocks($companyId);

        return response()->json([
            'success' => true,
            'data' => $locks,
        ]);
    }

    /**
     * Create a period lock.
     */
    public function storePeriodLock(Request $request): JsonResponse
    {
        $this->authorize('manage-closings');

        $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'notes' => 'nullable|string|max:1000',
        ]);

        $companyId = $request->header('company');

        // Check for overlapping locks
        $overlapping = PeriodLock::getOverlappingLocks(
            $companyId,
            $request->period_start,
            $request->period_end
        );

        if ($overlapping->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'This period overlaps with an existing lock.',
                'overlapping' => $overlapping,
            ], 422);
        }

        $lock = $this->lockService->lockPeriod(
            $companyId,
            $request->period_start,
            $request->period_end,
            auth()->id(),
            $request->notes
        );

        return response()->json([
            'success' => true,
            'message' => 'Period locked successfully.',
            'data' => $lock->load('lockedBy'),
        ], 201);
    }

    /**
     * Delete a period lock.
     */
    public function destroyPeriodLock(Request $request, int $id): JsonResponse
    {
        $this->authorize('manage-closings');

        $companyId = $request->header('company');

        $lock = PeriodLock::where('company_id', $companyId)
            ->findOrFail($id);

        $lock->delete();

        return response()->json([
            'success' => true,
            'message' => 'Period unlocked successfully.',
        ]);
    }

    /**
     * Check if a specific date is locked.
     */
    public function checkDate(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
            'type' => 'nullable|string',
        ]);

        $companyId = $request->header('company');
        $type = $request->type ?? DailyClosing::TYPE_ALL;

        $lockReason = $this->lockService->getLockReason($companyId, $request->date, $type);

        return response()->json([
            'success' => true,
            'locked' => $lockReason !== null,
            'reason' => $lockReason,
        ]);
    }
}
// CLAUDE-CHECKPOINT
