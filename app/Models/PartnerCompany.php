<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PartnerCompany extends Pivot
{
    /**
     * The table associated with the model.
     */
    protected $table = 'partner_company_links';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = true;

    /**
     * The "type" of the primary key ID.
     */
    protected $keyType = 'int';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'partner_id',
        'company_id',
        'is_primary',
        'override_commission_rate',
        'permissions',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'override_commission_rate' => 'decimal:2',
        'permissions' => 'array',
    ];

    /**
     * Get the partner that owns the link.
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Get the company that owns the link.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope a query to only include active links.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include primary company links.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Get the effective commission rate for this partner-company link.
     * Uses override rate if set, otherwise falls back to partner's default rate.
     */
    public function getEffectiveCommissionRateAttribute(): ?string
    {
        return $this->override_commission_rate ?? $this->partner->commission_rate;
    }
}
