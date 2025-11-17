<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Partner extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'company_name',
        'tax_id',
        'registration_number',
        'bank_account',
        'bank_name',
        'commission_rate',
        'is_active',
        'kyc_status',
        'user_id',
        'notes'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'commission_rate' => 'decimal:2'
    ];

    /**
     * Get the user associated with the partner
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the commissions for the partner
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    /**
     * Get the companies managed by this partner
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'partner_company_links')
                    ->using(PartnerCompany::class)
                    ->withPivot([
                        'id',
                        'is_primary',
                        'override_commission_rate',
                        'permissions',
                        'is_active'
                    ])
                    ->withTimestamps();
    }

    /**
     * Get only active companies for this partner
     */
    public function activeCompanies(): BelongsToMany
    {
        return $this->companies()->wherePivot('is_active', true);
    }

    /**
     * Get the primary company for this partner
     */
    public function primaryCompany(): BelongsToMany
    {
        return $this->companies()->wherePivot('is_primary', true);
    }

    /**
     * Scope a query to only include active partners
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the affiliate links for this partner
     */
    public function affiliateLinks(): HasMany
    {
        return $this->hasMany(AffiliateLink::class);
    }

    /**
     * Get commission events where this partner is the affiliate
     */
    public function affiliateEvents(): HasMany
    {
        return $this->hasMany(AffiliateEvent::class, 'affiliate_partner_id');
    }

    /**
     * Get commission events where this partner is the upline
     */
    public function uplineEvents(): HasMany
    {
        return $this->hasMany(AffiliateEvent::class, 'upline_partner_id');
    }

    /**
     * Get payouts for this partner
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    /**
     * Get KYC documents for this partner
     */
    public function kycDocuments(): HasMany
    {
        return $this->hasMany(KycDocument::class);
    }

    /**
     * Get the effective commission rate for this partner
     * (considers Partner Plus status)
     */
    public function getEffectiveCommissionRate(): float
    {
        if ($this->isPartnerPlus()) {
            return config('affiliate.direct_rate_plus', 0.22);
        }

        return config('affiliate.direct_rate', 0.20);
    }

    /**
     * Check if partner qualifies for Partner Plus status
     * Checks both paid subscription AND company count
     */
    public function isPartnerPlus(): bool
    {
        // First check if user has paid Partner Plus subscription
        if ($this->user && $this->user->partner_subscription_tier === 'plus') {
            return true;
        }

        // Alternative: auto-qualify based on performance metrics
        $minCompanies = config('affiliate.plus_tier_min_companies', 10);
        $activeCompaniesCount = $this->activeCompanies()->count();

        if ($activeCompaniesCount >= $minCompanies) {
            return true;
        }

        return false;
    }

    /**
     * Calculate total unpaid commissions for this partner
     */
    public function getUnpaidCommissionsTotal(): float
    {
        return $this->affiliateEvents()
            ->unpaid()
            ->sum('amount');
    }

    /**
     * Calculate lifetime earnings for this partner
     */
    public function getLifetimeEarnings(): float
    {
        return $this->affiliateEvents()
            ->where('is_clawed_back', false)
            ->sum('amount');
    }

    /**
     * Check if partner has specific permission for a company (AC-13)
     *
     * @param int $companyId
     * @param \App\Enums\PartnerPermission $permission
     * @return bool
     */
    public function hasPermission(int $companyId, \App\Enums\PartnerPermission $permission): bool
    {
        $link = $this->companies()
            ->where('companies.id', $companyId)
            ->first();

        if (!$link) {
            return false;
        }

        $permissions = $link->pivot->permissions ?? [];

        // Decode JSON if it's a string
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?? [];
        }

        // Full access overrides everything
        if (in_array(\App\Enums\PartnerPermission::FULL_ACCESS->value, $permissions)) {
            return true;
        }

        return in_array($permission->value, $permissions);
    }

    /**
     * Check if partner has any of the specified permissions for a company
     *
     * @param int $companyId
     * @param array<\App\Enums\PartnerPermission> $permissions
     * @return bool
     */
    public function hasAnyPermission(int $companyId, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($companyId, $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if partner has all of the specified permissions for a company
     *
     * @param int $companyId
     * @param array<\App\Enums\PartnerPermission> $permissions
     * @return bool
     */
    public function hasAllPermissions(int $companyId, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($companyId, $permission)) {
                return false;
            }
        }

        return true;
    }
}

// CLAUDE-CHECKPOINT