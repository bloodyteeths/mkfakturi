<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Daily Closing Model
 *
 * Represents a closed day for a company.
 * When a day is closed, documents with that date cannot be created/edited/deleted.
 *
 * Types:
 * - 'all': All document types are locked
 * - 'cash': Only cash-related documents locked
 * - 'invoices': Only invoices locked
 *
 * @property int $id
 * @property int $company_id
 * @property string $date
 * @property string $type
 * @property int|null $closed_by
 * @property \Carbon\Carbon $closed_at
 * @property string|null $notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class DailyClosing extends Model
{
    use HasFactory;

    protected $table = 'daily_closings';

    protected $fillable = [
        'company_id',
        'date',
        'type',
        'closed_by',
        'closed_at',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'closed_at' => 'datetime',
    ];

    // Closing types
    public const TYPE_ALL = 'all';

    public const TYPE_CASH = 'cash';

    public const TYPE_INVOICES = 'invoices';

    /**
     * Get the company that owns the closing.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who closed the day.
     */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /**
     * Scope to filter by company.
     */
    public function scopeWhereCompany($query)
    {
        return $query->where('company_id', request()->header('company'));
    }

    /**
     * Scope to filter by date.
     */
    public function scopeForDate($query, $date)
    {
        return $query->where('date', $date);
    }

    /**
     * Scope to filter by type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Check if a specific date is closed for the given type.
     */
    public static function isDateClosed(int $companyId, $date, string $type = self::TYPE_ALL): bool
    {
        $dateStr = $date instanceof \Carbon\Carbon ? $date->format('Y-m-d') : $date;

        return self::where('company_id', $companyId)
            ->whereDate('date', $dateStr)
            ->where(function ($query) use ($type) {
                $query->where('type', self::TYPE_ALL)
                    ->orWhere('type', $type);
            })
            ->exists();
    }
}
// CLAUDE-CHECKPOINT
