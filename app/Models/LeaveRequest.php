<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Leave Request Model
 *
 * Represents an employee's leave request with approval workflow.
 * Supports statuses: pending, approved, rejected, cancelled.
 * Includes overlap detection to prevent conflicting approved leaves.
 *
 * @property int $id
 * @property int $company_id
 * @property int $employee_id
 * @property int $leave_type_id
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property int $business_days
 * @property string $status
 * @property string|null $reason
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property string|null $rejection_reason
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class LeaveRequest extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** @var string Status for pending requests */
    public const STATUS_PENDING = 'pending';

    /** @var string Status for approved requests */
    public const STATUS_APPROVED = 'approved';

    /** @var string Status for rejected requests */
    public const STATUS_REJECTED = 'rejected';

    /** @var string Status for cancelled requests */
    public const STATUS_CANCELLED = 'cancelled';

    protected $guarded = [
        'id',
    ];

    protected $fillable = [
        'company_id',
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'business_days',
        'status',
        'reason',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'approved_at' => 'datetime',
            'business_days' => 'integer',
        ];
    }

    /**
     * Boot the model.
     *
     * Registers a creating event to prevent overlapping approved leaves
     * for the same employee.
     */
    protected static function booted(): void
    {
        static::creating(function (LeaveRequest $request) {
            // Check for overlapping approved or pending leaves for the same employee
            $overlapping = static::where('employee_id', $request->employee_id)
                ->whereIn('status', [self::STATUS_APPROVED, self::STATUS_PENDING])
                ->where('start_date', '<=', $request->end_date)
                ->where('end_date', '>=', $request->start_date)
                ->exists();

            if ($overlapping) {
                throw new \Illuminate\Validation\ValidationException(
                    validator([], []),
                    response()->json([
                        'error' => 'overlapping_leave',
                        'message' => 'This leave request overlaps with an existing approved or pending leave.',
                    ], 422)
                );
            }
        });
    }

    /**
     * Get the company that owns this leave request.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the employee who made this leave request.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(PayrollEmployee::class, 'employee_id');
    }

    /**
     * Get the leave type for this request.
     */
    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * Get the user who approved/rejected this request.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Approve this leave request.
     *
     * @param int $userId The ID of the user approving the request
     * @return bool
     */
    public function approve(int $userId): bool
    {
        return $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    /**
     * Reject this leave request.
     *
     * @param int $userId The ID of the user rejecting the request
     * @param string $reason The reason for rejection
     * @return bool
     */
    public function reject(int $userId, string $reason): bool
    {
        return $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_by' => $userId,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Cancel this leave request.
     *
     * @return bool
     */
    public function cancel(): bool
    {
        return $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);
    }

    /**
     * Scope to get only pending requests.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get only approved requests.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope to filter by employee.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $employeeId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope to filter by date period.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Support\Carbon|string $start
     * @param \Illuminate\Support\Carbon|string $end
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForPeriod($query, $start, $end)
    {
        return $query->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start);
    }

    /**
     * Scope to find overlapping leaves for an employee.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $employeeId
     * @param \Illuminate\Support\Carbon|string $start
     * @param \Illuminate\Support\Carbon|string $end
     * @param int|null $excludeId ID to exclude (for updates)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOverlapping($query, int $employeeId, $start, $end, ?int $excludeId = null)
    {
        $query->where('employee_id', $employeeId)
            ->whereIn('status', [self::STATUS_APPROVED, self::STATUS_PENDING])
            ->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query;
    }
}

// CLAUDE-CHECKPOINT
