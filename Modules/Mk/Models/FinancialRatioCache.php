<?php

namespace Modules\Mk\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialRatioCache extends Model
{
    use HasFactory;

    protected $table = 'financial_ratio_cache';

    protected $fillable = [
        'company_id',
        'period_date',
        'ratio_type',
        'ratio_value',
        'metadata',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'period_date' => 'date',
            'ratio_value' => 'decimal:4',
            'metadata' => 'array',
            'calculated_at' => 'datetime',
        ];
    }

    // ---- Relationships ----

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // ---- Scopes ----

    /**
     * Scope to a specific company.
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('financial_ratio_cache.company_id', $companyId);
    }

    /**
     * Scope to a specific ratio type.
     */
    public function scopeOfType($query, string $ratioType)
    {
        return $query->where('financial_ratio_cache.ratio_type', $ratioType);
    }

    /**
     * Scope to a specific period date.
     */
    public function scopeForPeriod($query, string $periodDate)
    {
        return $query->where('financial_ratio_cache.period_date', $periodDate);
    }
}

// CLAUDE-CHECKPOINT
