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
 * Tax Report Period Model
 *
 * Represents a tax reporting period (monthly, quarterly, or annual) for a company.
 * Periods can be opened, closed, filed, or amended. When closed, all related
 * documents (invoices, payments) are locked from editing.
 *
 * @property int $id
 * @property int $company_id
 * @property string $period_type Monthly, quarterly, or annual period
 * @property int $year Fiscal year
 * @property int|null $month Month (1-12) for monthly periods
 * @property int|null $quarter Quarter (1-4) for quarterly periods
 * @property Carbon $start_date Period start date
 * @property Carbon $end_date Period end date
 * @property string $status Period status (OPEN, CLOSED, FILED, AMENDED)
 * @property Carbon|null $closed_at Timestamp when period was closed
 * @property int|null $closed_by_id User who closed the period
 * @property Carbon|null $reopened_at Timestamp when period was last reopened
 * @property int|null $reopened_by_id User who reopened the period
 * @property string|null $reopen_reason Reason for reopening period
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class TaxReportPeriod extends Model
{
    use HasAuditing;
    use HasFactory;
    use TenantScope;

    /**
     * Period status constants
     */
    public const STATUS_OPEN = 'OPEN';

    public const STATUS_CLOSED = 'CLOSED';

    public const STATUS_FILED = 'FILED';

    public const STATUS_AMENDED = 'AMENDED';

    /**
     * Period type constants
     */
    public const PERIOD_MONTHLY = 'MONTHLY';

    public const PERIOD_QUARTERLY = 'QUARTERLY';

    public const PERIOD_ANNUAL = 'ANNUAL';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'company_id',
        'period_type',
        'year',
        'month',
        'quarter',
        'start_date',
        'end_date',
        'status',
        'closed_at',
        'closed_by_id',
        'reopened_at',
        'reopened_by_id',
        'reopen_reason',
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
            'year' => 'integer',
            'month' => 'integer',
            'quarter' => 'integer',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'closed_at' => 'datetime',
            'reopened_at' => 'datetime',
        ];
    }

    /**
     * Get the company that owns the tax report period.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get all tax returns for this period.
     */
    public function taxReturns(): HasMany
    {
        return $this->hasMany(TaxReturn::class, 'period_id');
    }

    /**
     * Get the user who closed this period.
     */
    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_id');
    }

    /**
     * Get the user who last reopened this period.
     */
    public function reopenedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by_id');
    }

    /**
     * Close the tax period.
     *
     * Validates that all tax returns for this period have been filed,
     * then marks the period as closed and locks all related documents.
     *
     * @param  int  $userId  User ID of person closing the period
     *
     * @throws \Exception If unfiled returns exist
     */
    public function close(int $userId): bool
    {
        // Check for unfiled tax returns
        if ($this->hasUnfiledReturns()) {
            throw new \Exception('Cannot close period with unfiled tax returns');
        }

        return DB::transaction(function () use ($userId) {
            $this->status = self::STATUS_CLOSED;
            $this->closed_at = now();
            $this->closed_by_id = $userId;

            return $this->save();
        });
    }

    /**
     * Reopen a closed tax period.
     *
     * Allows reopening for amendments or corrections.
     * Requires a reason for audit trail purposes.
     *
     * @param  int  $userId  User ID of person reopening the period
     * @param  string  $reason  Reason for reopening
     */
    public function reopen(int $userId, string $reason): bool
    {
        if (! $this->isClosed()) {
            throw new \Exception('Can only reopen closed periods');
        }

        return DB::transaction(function () use ($userId, $reason) {
            $this->status = self::STATUS_OPEN;
            $this->reopened_at = now();
            $this->reopened_by_id = $userId;
            $this->reopen_reason = $reason;

            return $this->save();
        });
    }

    /**
     * Check if the period is closed.
     */
    public function isClosed(): bool
    {
        return in_array($this->status, [
            self::STATUS_CLOSED,
            self::STATUS_FILED,
            self::STATUS_AMENDED,
        ]);
    }

    /**
     * Check if the period is locked (prevents document edits).
     *
     * A period is locked when it's closed, filed, or amended.
     */
    public function isLocked(): bool
    {
        return $this->isClosed();
    }

    /**
     * Check if this period has any unfiled tax returns.
     */
    public function hasUnfiledReturns(): bool
    {
        return $this->taxReturns()
            ->where('status', '!=', TaxReturn::STATUS_FILED)
            ->where('status', '!=', TaxReturn::STATUS_ACCEPTED)
            ->exists();
    }

    /**
     * Scope: Filter periods by company.
     *
     * Uses request header if no company ID provided.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereCompany($query, ?int $companyId = null)
    {
        $companyId = $companyId ?? request()->header('company');

        return $query->where('company_id', $companyId);
    }

    /**
     * Scope: Filter open periods.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    /**
     * Scope: Filter closed periods.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeClosed($query)
    {
        return $query->whereIn('status', [
            self::STATUS_CLOSED,
            self::STATUS_FILED,
            self::STATUS_AMENDED,
        ]);
    }

    /**
     * Scope: Filter filed periods.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFiled($query)
    {
        return $query->whereIn('status', [
            self::STATUS_FILED,
            self::STATUS_AMENDED,
        ]);
    }

    /**
     * Scope: Filter by specific period (year, month, quarter).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int|null  $month  Month (1-12)
     * @param  int|null  $quarter  Quarter (1-4)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForPeriod($query, int $year, ?int $month = null, ?int $quarter = null)
    {
        $query->where('year', $year);

        if ($month !== null) {
            $query->where('month', $month);
        }

        if ($quarter !== null) {
            $query->where('quarter', $quarter);
        }

        return $query;
    }

    /**
     * Scope: Order by period date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByPeriod($query, string $direction = 'desc')
    {
        return $query->orderBy('year', $direction)
            ->orderBy('quarter', $direction)
            ->orderBy('month', $direction);
    }

    /**
     * Scope: Paginate data.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int|string  $limit
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
     * Get a human-readable period name.
     */
    public function getPeriodNameAttribute(): string
    {
        switch ($this->period_type) {
            case self::PERIOD_MONTHLY:
                return Carbon::createFromDate($this->year, $this->month, 1)
                    ->format('F Y');

            case self::PERIOD_QUARTERLY:
                return "Q{$this->quarter} {$this->year}";

            case self::PERIOD_ANNUAL:
                return "FY {$this->year}";

            default:
                return "{$this->period_type} {$this->year}";
        }
    }
}

// CLAUDE-CHECKPOINT
