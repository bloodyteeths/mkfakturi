<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * UsageTracking Model
 *
 * Tracks usage counters for subscription features.
 * Used by UsageLimitService to enforce tier limits.
 */
class UsageTracking extends Model
{
    use HasFactory;

    protected $table = 'usage_tracking';

    protected $fillable = [
        'company_id',
        'feature',
        'count',
        'period',
    ];

    protected $casts = [
        'count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the company that owns this usage record
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope to filter by current month
     */
    public function scopeCurrentMonth($query)
    {
        return $query->where('period', now()->format('Y-m'));
    }

    /**
     * Scope to filter by feature
     */
    public function scopeForFeature($query, string $feature)
    {
        return $query->where('feature', $feature);
    }

    /**
     * Scope to filter by company
     */
    public function scopeForCompany($query, Company $company)
    {
        return $query->where('company_id', $company->id);
    }

    /**
     * Check if this is a monthly tracking record
     */
    public function isMonthly(): bool
    {
        return $this->period !== 'total' && preg_match('/^\d{4}-\d{2}$/', $this->period);
    }
}
// CLAUDE-CHECKPOINT
