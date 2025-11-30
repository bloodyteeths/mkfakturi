<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Period Lock Model
 *
 * Represents a locked period (date range) for a company.
 * All documents with dates in this range are protected from changes.
 *
 * Typically used for:
 * - Locking an entire month after export to external accounting
 * - Locking a quarter for fiscal compliance
 *
 * @property int $id
 * @property int $company_id
 * @property string $period_start
 * @property string $period_end
 * @property int|null $locked_by
 * @property \Carbon\Carbon $locked_at
 * @property string|null $notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PeriodLock extends Model
{
    use HasFactory;

    protected $table = 'period_locks';

    protected $fillable = [
        'company_id',
        'period_start',
        'period_end',
        'locked_by',
        'locked_at',
        'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'locked_at' => 'datetime',
    ];

    /**
     * Get the company that owns the lock.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who locked the period.
     */
    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    /**
     * Scope to filter by company.
     */
    public function scopeWhereCompany($query)
    {
        return $query->where('company_id', request()->header('company'));
    }

    /**
     * Scope to find locks that contain a specific date.
     */
    public function scopeContainingDate($query, $date)
    {
        return $query->where('period_start', '<=', $date)
            ->where('period_end', '>=', $date);
    }

    /**
     * Check if a specific date falls within any locked period.
     */
    public static function isDateLocked(int $companyId, $date): bool
    {
        $dateStr = $date instanceof \Carbon\Carbon ? $date->format('Y-m-d') : $date;

        return self::where('company_id', $companyId)
            ->whereDate('period_start', '<=', $dateStr)
            ->whereDate('period_end', '>=', $dateStr)
            ->exists();
    }

    /**
     * Get all locks that overlap with a given date range.
     */
    public static function getOverlappingLocks(int $companyId, $startDate, $endDate)
    {
        return self::where('company_id', $companyId)
            ->where(function ($query) use ($startDate, $endDate) {
                // Lock overlaps with range if:
                // lock_start <= end_date AND lock_end >= start_date
                $query->where('period_start', '<=', $endDate)
                    ->where('period_end', '>=', $startDate);
            })
            ->get();
    }

    /**
     * Get formatted period string.
     */
    public function getFormattedPeriodAttribute(): string
    {
        return $this->period_start->format('Y-m-d').' - '.$this->period_end->format('Y-m-d');
    }
}
// CLAUDE-CHECKPOINT
