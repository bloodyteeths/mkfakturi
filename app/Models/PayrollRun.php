<?php

namespace App\Models;

use App\Traits\HasAuditing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    use HasFactory;
    use HasAuditing;

    // Status constants
    public const STATUS_DRAFT = 'draft';
    public const STATUS_CALCULATED = 'calculated';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_POSTED = 'posted';
    public const STATUS_PAID = 'paid';

    protected $guarded = [
        'id',
    ];

    protected $fillable = [
        'company_id',
        'period_year',
        'period_month',
        'period_start',
        'period_end',
        'status',
        'total_gross',
        'total_net',
        'total_employer_tax',
        'total_employee_tax',
        'ifrs_transaction_id',
        'calculated_at',
        'approved_at',
        'posted_at',
        'paid_at',
        'approved_by',
        'bank_file_generated_at',
        'bank_file_path',
        'creator_id',
    ];

    protected $dates = [
        'period_start',
        'period_end',
        'calculated_at',
        'approved_at',
        'posted_at',
        'paid_at',
        'bank_file_generated_at',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'period_year' => 'integer',
            'period_month' => 'integer',
            'total_gross' => 'integer',
            'total_net' => 'integer',
            'total_employer_tax' => 'integer',
            'total_employee_tax' => 'integer',
            'period_start' => 'date',
            'period_end' => 'date',
            'calculated_at' => 'datetime',
            'approved_at' => 'datetime',
            'posted_at' => 'datetime',
            'paid_at' => 'datetime',
            'bank_file_generated_at' => 'datetime',
        ];
    }

    /**
     * Get the company that owns the payroll run.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the payroll run lines.
     */
    public function lines(): HasMany
    {
        return $this->hasMany(PayrollRunLine::class, 'payroll_run_id');
    }

    /**
     * Get the user who created this payroll run.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Get the user who approved this payroll run.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the period name (e.g., "January 2025").
     */
    public function getPeriodNameAttribute(): string
    {
        $monthName = date('F', mktime(0, 0, 0, $this->period_month, 1));
        return "{$monthName} {$this->period_year}";
    }

    /**
     * Check if the payroll run is editable.
     */
    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_CALCULATED]);
    }

    /**
     * Check if the payroll run can be calculated.
     */
    public function canCalculate(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if the payroll run can be approved.
     */
    public function canApprove(): bool
    {
        return $this->status === self::STATUS_CALCULATED;
    }

    /**
     * Check if the payroll run can be posted to IFRS.
     */
    public function canPost(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if the payroll run can be marked as paid.
     */
    public function canMarkPaid(): bool
    {
        return $this->status === self::STATUS_POSTED;
    }

    /**
     * Scope to get payroll runs by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get payroll runs for a specific period.
     */
    public function scopeForPeriod($query, int $year, int $month)
    {
        return $query->where('period_year', $year)
            ->where('period_month', $month);
    }

    /**
     * Scope to filter by company.
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}

// LLM-CHECKPOINT
