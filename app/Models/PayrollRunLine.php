<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollRunLine extends Model
{
    use HasFactory;

    protected $guarded = [
        'id',
    ];

    protected $fillable = [
        'payroll_run_id',
        'employee_id',
        'working_days',
        'worked_days',
        'gross_salary',
        'net_salary',
        'income_tax_amount',
        'pension_contribution_employee',
        'pension_contribution_employer',
        'health_contribution_employee',
        'health_contribution_employer',
        'unemployment_contribution',
        'additional_contribution',
        'transport_allowance',
        'meal_allowance',
        'other_additions',
        'deductions',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'working_days' => 'integer',
            'worked_days' => 'integer',
            'gross_salary' => 'integer',
            'net_salary' => 'integer',
            'income_tax_amount' => 'integer',
            'pension_contribution_employee' => 'integer',
            'pension_contribution_employer' => 'integer',
            'health_contribution_employee' => 'integer',
            'health_contribution_employer' => 'integer',
            'unemployment_contribution' => 'integer',
            'additional_contribution' => 'integer',
            'transport_allowance' => 'integer',
            'meal_allowance' => 'integer',
            'other_additions' => 'array',
            'deductions' => 'array',
        ];
    }

    /**
     * Get the payroll run that owns this line.
     */
    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    /**
     * Get the employee for this payroll line.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(PayrollEmployee::class, 'employee_id');
    }

    /**
     * Get the total employee contributions.
     */
    public function getTotalEmployeeContributionsAttribute(): int
    {
        return $this->pension_contribution_employee +
            $this->health_contribution_employee +
            $this->unemployment_contribution +
            $this->additional_contribution;
    }

    /**
     * Get the total employer contributions.
     */
    public function getTotalEmployerContributionsAttribute(): int
    {
        return $this->pension_contribution_employer +
            $this->health_contribution_employer;
    }

    /**
     * Get the total deductions (employee contributions + income tax).
     */
    public function getTotalDeductionsAttribute(): int
    {
        return $this->total_employee_contributions + $this->income_tax_amount;
    }

    /**
     * Get the total cost to employer (gross + employer contributions).
     */
    public function getTotalEmployerCostAttribute(): int
    {
        return $this->gross_salary + $this->total_employer_contributions;
    }

    /**
     * Get total from other additions.
     */
    public function getOtherAdditionsTotalAttribute(): int
    {
        if (!$this->other_additions || !is_array($this->other_additions)) {
            return 0;
        }

        $total = 0;
        foreach ($this->other_additions as $addition) {
            if (isset($addition['amount'])) {
                $total += (int) $addition['amount'];
            }
        }

        return $total;
    }

    /**
     * Get total from deductions.
     */
    public function getDeductionsTotalAttribute(): int
    {
        if (!$this->deductions || !is_array($this->deductions)) {
            return 0;
        }

        $total = 0;
        foreach ($this->deductions as $deduction) {
            if (isset($deduction['amount'])) {
                $total += (int) $deduction['amount'];
            }
        }

        return $total;
    }

    /**
     * Scope to get included lines only.
     */
    public function scopeIncluded($query)
    {
        return $query->where('status', 'included');
    }

    /**
     * Scope to get excluded lines only.
     */
    public function scopeExcluded($query)
    {
        return $query->where('status', 'excluded');
    }
}

// LLM-CHECKPOINT
