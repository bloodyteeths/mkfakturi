<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Deadline Model (P8-02)
 *
 * Represents an accounting deadline for a company, such as VAT returns,
 * MPIN filings, CIT advance payments, annual financial statements, or
 * custom deadlines created by partners.
 *
 * @property int $id
 * @property int $company_id
 * @property int|null $partner_id
 * @property string $title
 * @property string|null $title_mk
 * @property string|null $description
 * @property string $deadline_type
 * @property Carbon $due_date
 * @property string $status
 * @property Carbon|null $completed_at
 * @property int|null $completed_by
 * @property array $reminder_days_before
 * @property Carbon|null $last_reminder_sent_at
 * @property bool $is_recurring
 * @property string|null $recurrence_rule
 * @property array|null $metadata
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Deadline extends Model
{
    use HasFactory;

    // Deadline types
    public const TYPE_VAT = 'vat_return';

    public const TYPE_MPIN = 'mpin';

    public const TYPE_CIT = 'cit_advance';

    public const TYPE_ANNUAL_FS = 'annual_fs';

    public const TYPE_CUSTOM = 'custom';

    // Status constants
    public const STATUS_UPCOMING = 'upcoming';

    public const STATUS_DUE_TODAY = 'due_today';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_COMPLETED = 'completed';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'company_id',
        'partner_id',
        'title',
        'title_mk',
        'description',
        'deadline_type',
        'due_date',
        'status',
        'completed_at',
        'completed_by',
        'reminder_days_before',
        'last_reminder_sent_at',
        'is_recurring',
        'recurrence_rule',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'last_reminder_sent_at' => 'datetime',
        'reminder_days_before' => 'array',
        'metadata' => 'array',
        'is_recurring' => 'boolean',
    ];

    /**
     * Boot the model.
     *
     * Auto-updates status based on due_date when retrieving records.
     */
    protected static function booted(): void
    {
        static::retrieved(function (Deadline $deadline) {
            if ($deadline->status === self::STATUS_COMPLETED) {
                return;
            }

            $today = Carbon::today();
            $dueDate = $deadline->due_date;

            if (! $dueDate) {
                return;
            }

            if ($dueDate->lt($today) && $deadline->status !== self::STATUS_OVERDUE) {
                $deadline->status = self::STATUS_OVERDUE;
                $deadline->saveQuietly();
            } elseif ($dueDate->eq($today) && $deadline->status !== self::STATUS_DUE_TODAY) {
                $deadline->status = self::STATUS_DUE_TODAY;
                $deadline->saveQuietly();
            }
        });
    }

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    /**
     * Get the company that owns the deadline.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the partner managing the deadline.
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Get the user who completed the deadline.
     */
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    // ──────────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────────

    /**
     * Scope to upcoming deadlines (not completed, not overdue).
     */
    public function scopeUpcoming($query)
    {
        return $query->where(function ($q) {
            $q->where('status', self::STATUS_UPCOMING)
                ->orWhere('status', self::STATUS_DUE_TODAY);
        });
    }

    /**
     * Scope to overdue deadlines.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_OVERDUE);
    }

    /**
     * Scope to due today deadlines.
     */
    public function scopeDueToday($query)
    {
        return $query->where('status', self::STATUS_DUE_TODAY);
    }

    /**
     * Scope to completed deadlines.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope to deadlines for a specific company.
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope to deadlines for a specific partner.
     */
    public function scopeForPartner($query, int $partnerId)
    {
        return $query->where('partner_id', $partnerId);
    }

    /**
     * Scope to deadlines due within a given number of days.
     */
    public function scopeDueWithinDays($query, int $days)
    {
        return $query->where('due_date', '<=', Carbon::today()->addDays($days))
            ->where('due_date', '>=', Carbon::today())
            ->where('status', '!=', self::STATUS_COMPLETED);
    }

    /**
     * Scope to deadlines of a specific type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('deadline_type', $type);
    }

    /**
     * Scope to non-completed deadlines.
     */
    public function scopeNotCompleted($query)
    {
        return $query->where('status', '!=', self::STATUS_COMPLETED);
    }

    // ──────────────────────────────────────────────
    // Methods
    // ──────────────────────────────────────────────

    /**
     * Mark the deadline as completed.
     *
     * @param  int  $userId  The ID of the user completing the deadline.
     * @return bool
     */
    public function complete(int $userId): bool
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = Carbon::now();
        $this->completed_by = $userId;

        return $this->save();
    }

    /**
     * Check if the deadline is overdue.
     */
    public function isOverdue(): bool
    {
        if ($this->status === self::STATUS_COMPLETED) {
            return false;
        }

        return $this->due_date->lt(Carbon::today());
    }

    /**
     * Check if the deadline is due today.
     */
    public function isDueToday(): bool
    {
        if ($this->status === self::STATUS_COMPLETED) {
            return false;
        }

        return $this->due_date->isToday();
    }

    /**
     * Check if a reminder should be sent for this deadline.
     *
     * Returns true if the current date is within one of the reminder
     * windows AND no reminder has been sent for this window yet.
     */
    public function needsReminder(): bool
    {
        if ($this->status === self::STATUS_COMPLETED) {
            return false;
        }

        $daysUntilDue = Carbon::today()->diffInDays($this->due_date, false);

        // Only send reminders for future or today deadlines
        if ($daysUntilDue < 0) {
            return false;
        }

        $reminderDays = $this->reminder_days_before ?? [7, 3, 1];

        // Check if today matches any reminder day
        if (! in_array((int) $daysUntilDue, $reminderDays)) {
            return false;
        }

        // Check if a reminder has already been sent today
        if ($this->last_reminder_sent_at && $this->last_reminder_sent_at->isToday()) {
            return false;
        }

        return true;
    }

    /**
     * Get the number of days remaining until the due date.
     * Negative values indicate overdue days.
     */
    public function getDaysRemainingAttribute(): int
    {
        return Carbon::today()->diffInDays($this->due_date, false);
    }

    /**
     * Get the localized type label.
     */
    public function getTypeLabelAttribute(): string
    {
        $labels = [
            self::TYPE_VAT => 'ДДВ пријава',
            self::TYPE_MPIN => 'МПИН пријава',
            self::TYPE_CIT => 'Аконтација на данок на добивка',
            self::TYPE_ANNUAL_FS => 'Годишна сметка',
            self::TYPE_CUSTOM => 'Прилагодено',
        ];

        return $labels[$this->deadline_type] ?? $this->deadline_type;
    }

    /**
     * Get the English type label.
     */
    public function getTypeLabelEnAttribute(): string
    {
        $labels = [
            self::TYPE_VAT => 'VAT Return',
            self::TYPE_MPIN => 'MPIN Filing',
            self::TYPE_CIT => 'CIT Advance',
            self::TYPE_ANNUAL_FS => 'Annual Financial Statement',
            self::TYPE_CUSTOM => 'Custom',
        ];

        return $labels[$this->deadline_type] ?? $this->deadline_type;
    }
}
// CLAUDE-CHECKPOINT
