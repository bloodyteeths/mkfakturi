<?php

namespace App\Services;

use App\Exceptions\PeriodLockedException;
use App\Models\DailyClosing;
use App\Models\PeriodLock;
use Carbon\Carbon;

/**
 * Period Lock Service
 *
 * Central service for checking and enforcing period locks and daily closings.
 * Used by models and controllers to prevent edits to locked periods.
 *
 * @version 1.0.0
 */
class PeriodLockService
{
    /**
     * Check if a date is locked (either by daily closing or period lock).
     *
     * @param  int  $companyId
     * @param  string|\Carbon\Carbon  $date
     * @param  string  $type  Document type for daily closings
     * @return bool
     */
    public function isDateLocked(int $companyId, $date, string $type = DailyClosing::TYPE_ALL): bool
    {
        $date = Carbon::parse($date)->format('Y-m-d');

        // Check daily closings first
        if (DailyClosing::isDateClosed($companyId, $date, $type)) {
            return true;
        }

        // Check period locks
        if (PeriodLock::isDateLocked($companyId, $date)) {
            return true;
        }

        return false;
    }

    /**
     * Get the reason why a date is locked.
     *
     * @param  int  $companyId
     * @param  string|\Carbon\Carbon  $date
     * @param  string  $type
     * @return array|null ['type' => 'daily'|'period', 'lock' => Model, 'message' => string]
     */
    public function getLockReason(int $companyId, $date, string $type = DailyClosing::TYPE_ALL): ?array
    {
        $dateStr = Carbon::parse($date)->format('Y-m-d');

        // Check daily closings
        $dailyClosing = DailyClosing::where('company_id', $companyId)
            ->whereDate('date', $dateStr)
            ->where(function ($query) use ($type) {
                $query->where('type', DailyClosing::TYPE_ALL)
                    ->orWhere('type', $type);
            })
            ->first();

        if ($dailyClosing) {
            return [
                'type' => 'daily',
                'lock' => $dailyClosing,
                'message' => "Day {$dateStr} is closed.",
            ];
        }

        // Check period locks
        $periodLock = PeriodLock::where('company_id', $companyId)
            ->whereDate('period_start', '<=', $dateStr)
            ->whereDate('period_end', '>=', $dateStr)
            ->first();

        if ($periodLock) {
            return [
                'type' => 'period',
                'lock' => $periodLock,
                'message' => "Date {$dateStr} is within locked period {$periodLock->period_start->format('Y-m-d')} to {$periodLock->period_end->format('Y-m-d')}.",
            ];
        }

        return null;
    }

    /**
     * Enforce that a date is not locked.
     * Throws exception if date is locked.
     *
     * @param  int  $companyId
     * @param  string|\Carbon\Carbon  $date
     * @param  string  $type
     * @param  string  $action  Action being attempted (for error message)
     *
     * @throws PeriodLockedException
     */
    public function enforceUnlocked(int $companyId, $date, string $type = DailyClosing::TYPE_ALL, string $action = 'modify'): void
    {
        $lockReason = $this->getLockReason($companyId, $date, $type);

        if ($lockReason) {
            throw new PeriodLockedException(
                "Cannot {$action} document: ".$lockReason['message'],
                $lockReason['type'],
                $lockReason['lock']
            );
        }
    }

    /**
     * Create a daily closing.
     *
     * @param  int  $companyId
     * @param  string|\Carbon\Carbon  $date
     * @param  string  $type
     * @param  int|null  $userId
     * @param  string|null  $notes
     * @return DailyClosing
     */
    public function closeDay(int $companyId, $date, string $type = DailyClosing::TYPE_ALL, ?int $userId = null, ?string $notes = null): DailyClosing
    {
        return DailyClosing::create([
            'company_id' => $companyId,
            'date' => Carbon::parse($date)->format('Y-m-d'),
            'type' => $type,
            'closed_by' => $userId ?? auth()->id(),
            'closed_at' => now(),
            'notes' => $notes,
        ]);
    }

    /**
     * Create a period lock.
     *
     * @param  int  $companyId
     * @param  string|\Carbon\Carbon  $startDate
     * @param  string|\Carbon\Carbon  $endDate
     * @param  int|null  $userId
     * @param  string|null  $notes
     * @return PeriodLock
     */
    public function lockPeriod(int $companyId, $startDate, $endDate, ?int $userId = null, ?string $notes = null): PeriodLock
    {
        return PeriodLock::create([
            'company_id' => $companyId,
            'period_start' => Carbon::parse($startDate)->format('Y-m-d'),
            'period_end' => Carbon::parse($endDate)->format('Y-m-d'),
            'locked_by' => $userId ?? auth()->id(),
            'locked_at' => now(),
            'notes' => $notes,
        ]);
    }

    /**
     * Delete a daily closing (unlock a day).
     *
     * @param  int  $closingId
     * @return bool
     */
    public function unlockDay(int $closingId): bool
    {
        $closing = DailyClosing::findOrFail($closingId);

        return $closing->delete();
    }

    /**
     * Delete a period lock (unlock a period).
     *
     * @param  int  $lockId
     * @return bool
     */
    public function unlockPeriod(int $lockId): bool
    {
        $lock = PeriodLock::findOrFail($lockId);

        return $lock->delete();
    }

    /**
     * Get all closed days for a company.
     *
     * @param  int  $companyId
     * @param  string|null  $fromDate
     * @param  string|null  $toDate
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getClosedDays(int $companyId, ?string $fromDate = null, ?string $toDate = null)
    {
        $query = DailyClosing::where('company_id', $companyId)
            ->with('closedBy')
            ->orderBy('date', 'desc');

        if ($fromDate) {
            $query->where('date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->where('date', '<=', $toDate);
        }

        return $query->get();
    }

    /**
     * Get all period locks for a company.
     *
     * @param  int  $companyId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPeriodLocks(int $companyId)
    {
        return PeriodLock::where('company_id', $companyId)
            ->with('lockedBy')
            ->orderBy('period_start', 'desc')
            ->get();
    }
}
// CLAUDE-CHECKPOINT
