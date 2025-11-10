<?php

namespace App\Models;

use App\Traits\HasAuditing;
use App\Traits\TenantScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Tax Return Model
 *
 * Represents a tax return submission for a specific period.
 * Tax returns can be drafted, filed, accepted, rejected, or amended.
 * Prevents duplicate filings for the same period (unless it's an amendment).
 *
 * @property int $id
 * @property int $company_id
 * @property int $period_id Tax report period ID
 * @property string $return_type Type of tax return (VAT, income, etc.)
 * @property string $status Return status (DRAFT, FILED, ACCEPTED, REJECTED, AMENDED)
 * @property array|null $return_data Tax return form data
 * @property array|null $response_data Response from tax authority
 * @property string|null $submission_reference Reference number from submission
 * @property Carbon|null $submitted_at Timestamp of submission
 * @property int|null $submitted_by_id User who submitted the return
 * @property Carbon|null $accepted_at Timestamp when accepted by tax authority
 * @property Carbon|null $rejected_at Timestamp when rejected by tax authority
 * @property string|null $rejection_reason Reason for rejection
 * @property int|null $amendment_of_id Original return ID if this is an amendment
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @package App\Models
 */
class TaxReturn extends Model
{
    use HasFactory;
    use HasAuditing;
    use TenantScope;

    /**
     * Tax return status constants
     */
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_FILED = 'FILED';
    public const STATUS_ACCEPTED = 'ACCEPTED';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_AMENDED = 'AMENDED';

    /**
     * Tax return type constants
     */
    public const TYPE_VAT = 'VAT';
    public const TYPE_INCOME = 'INCOME';
    public const TYPE_PAYROLL = 'PAYROLL';
    public const TYPE_CORPORATE = 'CORPORATE';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'company_id',
        'period_id',
        'return_type',
        'status',
        'return_data',
        'response_data',
        'submission_reference',
        'submitted_at',
        'submitted_by_id',
        'accepted_at',
        'rejected_at',
        'rejection_reason',
        'amendment_of_id',
    ];

    /**
     * The attributes that should be guarded from mass assignment.
     *
     * @var array<string>
     */
    protected $guarded = [
        'id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'return_data' => 'array',
            'response_data' => 'array',
            'submitted_at' => 'datetime',
            'accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    /**
     * Get the company that owns the tax return.
     *
     * @return BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the tax report period this return belongs to.
     *
     * @return BelongsTo
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(TaxReportPeriod::class, 'period_id');
    }

    /**
     * Get the user who submitted this return.
     *
     * @return BelongsTo
     */
    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_id');
    }

    /**
     * Get the original return if this is an amendment.
     *
     * @return BelongsTo
     */
    public function amendmentOf(): BelongsTo
    {
        return $this->belongsTo(TaxReturn::class, 'amendment_of_id');
    }

    /**
     * Get all amendments of this return.
     *
     * @return HasMany
     */
    public function amendments(): HasMany
    {
        return $this->hasMany(TaxReturn::class, 'amendment_of_id');
    }

    /**
     * File the tax return.
     *
     * Validates that no duplicate filing exists for the same period,
     * then marks the return as filed with submission details.
     *
     * @param int $userId User ID of person filing the return
     * @param string|null $reference Submission reference number
     * @param array|null $responseData Response data from tax authority
     * @return bool
     * @throws \Exception If duplicate filing exists
     */
    public function file(int $userId, ?string $reference = null, ?array $responseData = null): bool
    {
        // Prevent duplicate filing for the same period (unless it's an amendment)
        if (!$this->amendment_of_id && $this->hasDuplicateFiling()) {
            throw new \Exception('A tax return has already been filed for this period');
        }

        return DB::transaction(function () use ($userId, $reference, $responseData) {
            $this->status = self::STATUS_FILED;
            $this->submitted_at = now();
            $this->submitted_by_id = $userId;
            $this->submission_reference = $reference;

            if ($responseData !== null) {
                $this->response_data = $responseData;
            }

            return $this->save();
        });
    }

    /**
     * Mark the tax return as accepted by the tax authority.
     *
     * @param array|null $responseData Response data from tax authority
     * @return bool
     */
    public function accept(?array $responseData = null): bool
    {
        return DB::transaction(function () use ($responseData) {
            $this->status = self::STATUS_ACCEPTED;
            $this->accepted_at = now();

            if ($responseData !== null) {
                $this->response_data = array_merge($this->response_data ?? [], $responseData);
            }

            // Update the period status to FILED if all returns are accepted
            if ($this->period && $this->period->status === TaxReportPeriod::STATUS_CLOSED) {
                $allAccepted = $this->period->taxReturns()
                    ->where('id', '!=', $this->id)
                    ->whereNotIn('status', [self::STATUS_ACCEPTED, self::STATUS_AMENDED])
                    ->doesntExist();

                if ($allAccepted) {
                    $this->period->status = TaxReportPeriod::STATUS_FILED;
                    $this->period->save();
                }
            }

            return $this->save();
        });
    }

    /**
     * Mark the tax return as rejected by the tax authority.
     *
     * @param string $reason Rejection reason
     * @param array|null $responseData Response data from tax authority
     * @return bool
     */
    public function reject(string $reason, ?array $responseData = null): bool
    {
        return DB::transaction(function () use ($reason, $responseData) {
            $this->status = self::STATUS_REJECTED;
            $this->rejected_at = now();
            $this->rejection_reason = $reason;

            if ($responseData !== null) {
                $this->response_data = array_merge($this->response_data ?? [], $responseData);
            }

            return $this->save();
        });
    }

    /**
     * Create an amendment for this tax return.
     *
     * Creates a new return record that references this one as the original.
     *
     * @param array $returnData New return data for the amendment
     * @return TaxReturn
     */
    public function amend(array $returnData): TaxReturn
    {
        if (!$this->canBeAmended()) {
            throw new \Exception('Only accepted returns can be amended');
        }

        return DB::transaction(function () use ($returnData) {
            // Mark current return as amended
            $this->status = self::STATUS_AMENDED;
            $this->save();

            // Create amendment return
            $amendment = self::create([
                'company_id' => $this->company_id,
                'period_id' => $this->period_id,
                'return_type' => $this->return_type,
                'status' => self::STATUS_DRAFT,
                'return_data' => $returnData,
                'amendment_of_id' => $this->id,
            ]);

            return $amendment;
        });
    }

    /**
     * Check if this return can be amended.
     *
     * @return bool
     */
    public function canBeAmended(): bool
    {
        return in_array($this->status, [
            self::STATUS_FILED,
            self::STATUS_ACCEPTED,
        ]);
    }

    /**
     * Check if a duplicate filing exists for this period.
     *
     * @return bool
     */
    protected function hasDuplicateFiling(): bool
    {
        return self::where('period_id', $this->period_id)
            ->where('return_type', $this->return_type)
            ->where('id', '!=', $this->id)
            ->whereIn('status', [
                self::STATUS_FILED,
                self::STATUS_ACCEPTED,
            ])
            ->exists();
    }

    /**
     * Scope: Filter returns by company.
     *
     * Uses request header if no company ID provided.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|null $companyId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereCompany($query, ?int $companyId = null)
    {
        $companyId = $companyId ?? request()->header('company');

        return $query->where('company_id', $companyId);
    }

    /**
     * Scope: Filter returns by period.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $periodId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForPeriod($query, int $periodId)
    {
        return $query->where('period_id', $periodId);
    }

    /**
     * Scope: Filter filed returns.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFiled($query)
    {
        return $query->whereIn('status', [
            self::STATUS_FILED,
            self::STATUS_ACCEPTED,
        ]);
    }

    /**
     * Scope: Filter by return type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('return_type', $type);
    }

    /**
     * Scope: Filter by status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Order by submission date.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderBySubmission($query, string $direction = 'desc')
    {
        return $query->orderBy('submitted_at', $direction);
    }

    /**
     * Scope: Paginate data.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|string $limit
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function scopePaginateData($query, $limit)
    {
        if ($limit == 'all') {
            return $query->get();
        }

        return $query->paginate($limit);
    }

    /**
     * Get a human-readable status label.
     *
     * @return string
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_FILED => 'Filed',
            self::STATUS_ACCEPTED => 'Accepted',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_AMENDED => 'Amended',
            default => $this->status,
        };
    }

    /**
     * Check if the return is editable.
     *
     * @return bool
     */
    public function isEditable(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }
}

// CLAUDE-CHECKPOINT
