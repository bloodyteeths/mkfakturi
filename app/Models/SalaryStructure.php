<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryStructure extends Model
{
    use HasFactory;

    protected $guarded = [
        'id',
    ];

    protected $fillable = [
        'company_id',
        'employee_id',
        'effective_from',
        'effective_to',
        'gross_salary',
        'transport_allowance',
        'meal_allowance',
        'other_allowances',
        'is_current',
    ];

    protected $dates = [
        'effective_from',
        'effective_to',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'gross_salary' => 'integer',
            'transport_allowance' => 'integer',
            'meal_allowance' => 'integer',
            'other_allowances' => 'array',
            'is_current' => 'boolean',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    /**
     * Get the company that owns the salary structure.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the employee that owns the salary structure.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(PayrollEmployee::class, 'employee_id');
    }

    /**
     * Get the total salary including allowances.
     */
    public function getTotalSalaryAttribute(): int
    {
        $total = $this->gross_salary + $this->transport_allowance + $this->meal_allowance;

        if ($this->other_allowances && is_array($this->other_allowances)) {
            foreach ($this->other_allowances as $allowance) {
                if (isset($allowance['amount'])) {
                    $total += (int) $allowance['amount'];
                }
            }
        }

        return $total;
    }

    /**
     * Scope to get current salary structures.
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    /**
     * Scope to get salary structures effective on a specific date.
     */
    public function scopeEffectiveOn($query, $date)
    {
        return $query->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $date);
            });
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
