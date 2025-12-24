<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollTaxRate extends Model
{
    use HasFactory;

    protected $guarded = [
        'id',
    ];

    protected $fillable = [
        'code',
        'name',
        'name_mk',
        'rate',
        'type',
        'effective_from',
        'effective_to',
        'is_active',
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
            'rate' => 'decimal:4',
            'is_active' => 'boolean',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    /**
     * Get the rate as a percentage (e.g., 9.00 for 0.09).
     */
    public function getRatePercentAttribute(): float
    {
        return $this->rate * 100;
    }

    /**
     * Calculate the contribution amount based on gross salary.
     *
     * @param int $grossSalary Amount in cents
     * @return int Contribution amount in cents
     */
    public function calculateContribution(int $grossSalary): int
    {
        return (int) round($grossSalary * $this->rate);
    }

    /**
     * Scope to get active tax rates only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get tax rates effective on a specific date.
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
     * Scope to get employee-type tax rates.
     */
    public function scopeEmployee($query)
    {
        return $query->whereIn('type', ['employee', 'both']);
    }

    /**
     * Scope to get employer-type tax rates.
     */
    public function scopeEmployer($query)
    {
        return $query->whereIn('type', ['employer', 'both']);
    }

    /**
     * Scope to get tax rate by code.
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }
}

// LLM-CHECKPOINT
