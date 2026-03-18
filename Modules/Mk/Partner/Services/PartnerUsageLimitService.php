<?php

namespace Modules\Mk\Partner\Services;

use App\Models\Partner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Partner Usage Limit Service
 *
 * Tracks and enforces usage limits for accountant subscription tiers.
 * All features available at every tier, differentiated by usage LIMITS only.
 * Hard block at limits: can view/download, can't create.
 */
class PartnerUsageLimitService
{
    /**
     * Monthly meters tracked in partner_usage_tracking table.
     */
    protected const MONTHLY_METERS = [
        'ai_credits_per_month',
        'efaktura_per_month',
        'documents_stored_per_month',
    ];

    /**
     * Total meters counted from live data across portfolio companies.
     */
    protected const TOTAL_METERS = [
        'companies',
        'bank_accounts',
        'payroll_employees',
        'client_portal_invites',
    ];

    /**
     * Get the partner's current subscription tier.
     * Validates trial status — expired trial with no subscription = 'free'.
     */
    public function getPartnerTier(Partner $partner): string
    {
        $user = $partner->user;
        if (!$user) {
            return 'free';
        }

        $tier = $user->partner_subscription_tier ?? 'free';

        // Check if on trial and trial has expired
        if ($this->isTrialExpired($partner) && !$user->stripe_subscription_id) {
            return 'free';
        }

        // Validate tier is a known value
        $validTiers = array_keys(config('subscriptions.partner_tiers', []));
        if (!in_array($tier, $validTiers)) {
            return 'free';
        }

        return $tier;
    }

    /**
     * Check if partner can use a meter (within limits).
     */
    public function canUse(Partner $partner, string $meter): bool
    {
        $usage = $this->getUsage($partner, $meter);

        if ($usage['limit'] === null) {
            return true;
        }

        return $usage['remaining'] > 0;
    }

    /**
     * Get usage statistics for a meter.
     *
     * @return array{used: int, limit: int|null, remaining: int|null}
     */
    public function getUsage(Partner $partner, string $meter): array
    {
        $tier = $this->getPartnerTier($partner);
        $limit = $this->getMeterLimit($tier, $meter);
        $used = $this->getUsedCount($partner, $meter);

        $remaining = $limit === null ? null : max(0, $limit - $used);

        return [
            'used' => $used,
            'limit' => $limit,
            'remaining' => $remaining,
        ];
    }

    /**
     * Increment a monthly usage counter for a partner.
     */
    public function incrementUsage(Partner $partner, string $meter, int $amount = 1): void
    {
        $period = $this->isMonthlyMeter($meter) ? now()->format('Y-m') : 'total';

        $existing = DB::table('partner_usage_tracking')
            ->where('partner_id', $partner->id)
            ->where('meter', $meter)
            ->where('period', $period)
            ->first();

        if ($existing) {
            DB::table('partner_usage_tracking')
                ->where('partner_id', $partner->id)
                ->where('meter', $meter)
                ->where('period', $period)
                ->increment('count', $amount);
        } else {
            DB::table('partner_usage_tracking')->insert([
                'partner_id' => $partner->id,
                'meter' => $meter,
                'period' => $period,
                'count' => $amount,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Log::debug('Partner usage incremented', [
            'partner_id' => $partner->id,
            'meter' => $meter,
            'period' => $period,
            'amount' => $amount,
        ]);
    }

    /**
     * Decrement a usage counter for a partner.
     */
    public function decrementUsage(Partner $partner, string $meter, int $amount = 1): void
    {
        $period = $this->isMonthlyMeter($meter) ? now()->format('Y-m') : 'total';

        DB::table('partner_usage_tracking')
            ->where('partner_id', $partner->id)
            ->where('meter', $meter)
            ->where('period', $period)
            ->where('count', '>', 0)
            ->decrement('count', $amount);
    }

    /**
     * Get all usage statistics for a partner (dashboard view).
     *
     * @return array<string, array{used: int, limit: int|null, remaining: int|null}>
     */
    public function getAllUsage(Partner $partner): array
    {
        $meters = array_merge(self::TOTAL_METERS, self::MONTHLY_METERS);
        $usage = [];

        foreach ($meters as $meter) {
            $usage[$meter] = $this->getUsage($partner, $meter);
        }

        return $usage;
    }

    /**
     * Check if partner is on an active trial.
     */
    public function isOnTrial(Partner $partner): bool
    {
        $user = $partner->user;
        if (!$user || !$user->partner_trial_ends_at) {
            return false;
        }

        // Has a trial end date that hasn't passed yet
        return now()->lt($user->partner_trial_ends_at)
            && !$user->stripe_subscription_id; // Not yet subscribed
    }

    /**
     * Get trial days remaining (-1 if not on trial).
     */
    public function getTrialDaysRemaining(Partner $partner): int
    {
        if (!$this->isOnTrial($partner)) {
            return -1;
        }

        return (int) now()->diffInDays($partner->user->partner_trial_ends_at, false);
    }

    /**
     * Check if trial has expired (had a trial, it ended, no subscription).
     */
    public function isTrialExpired(Partner $partner): bool
    {
        $user = $partner->user;
        if (!$user || !$user->partner_trial_ends_at) {
            return false;
        }

        return now()->gte($user->partner_trial_ends_at)
            && !$user->stripe_subscription_id;
    }

    /**
     * Check if the partner is hard-blocked (trial expired + no subscription).
     */
    public function isHardBlocked(Partner $partner): bool
    {
        return $this->getPartnerTier($partner) === 'free'
            && $partner->user
            && $partner->user->partner_trial_ends_at !== null; // Had a trial = not a brand new account
    }

    /**
     * Get the limit for a specific meter in a tier.
     */
    protected function getMeterLimit(string $tier, string $meter): ?int
    {
        return config("subscriptions.partner_tiers.{$tier}.limits.{$meter}");
    }

    /**
     * Get the current used count for a meter.
     */
    protected function getUsedCount(Partner $partner, string $meter): int
    {
        if ($this->isMonthlyMeter($meter)) {
            return $this->getMonthlyCount($partner, $meter);
        }

        return $this->countPortfolioUsage($partner, $meter);
    }

    /**
     * Get monthly usage from partner_usage_tracking table.
     */
    protected function getMonthlyCount(Partner $partner, string $meter): int
    {
        $period = now()->format('Y-m');

        $record = DB::table('partner_usage_tracking')
            ->where('partner_id', $partner->id)
            ->where('meter', $meter)
            ->where('period', $period)
            ->first();

        return $record ? (int) $record->count : 0;
    }

    /**
     * Count actual usage from live data across portfolio companies.
     */
    public function countPortfolioUsage(Partner $partner, string $meter): int
    {
        $companyIds = $partner->portfolioCompanies()->pluck('companies.id')->toArray();

        if (empty($companyIds)) {
            return 0;
        }

        switch ($meter) {
            case 'companies':
                return count($companyIds);

            case 'bank_accounts':
                return DB::table('bank_accounts')
                    ->whereIn('company_id', $companyIds)
                    ->count();

            case 'payroll_employees':
                return DB::table('payroll_employees')
                    ->whereIn('company_id', $companyIds)
                    ->where('is_active', true)
                    ->whereNull('deleted_at')
                    ->count();

            case 'client_portal_invites':
                return DB::table('users')
                    ->whereIn('company_id', $companyIds)
                    ->where('role', 'client')
                    ->count();

            default:
                // For monthly meters, fallback to tracking table
                return $this->getMonthlyCount($partner, $meter);
        }
    }

    /**
     * Check if a meter is monthly (resets each month).
     */
    protected function isMonthlyMeter(string $meter): bool
    {
        return in_array($meter, self::MONTHLY_METERS);
    }

    /**
     * Get the next tier that would increase limits for a meter.
     */
    public function getUpgradeTier(string $currentTier, string $meter): ?string
    {
        $hierarchy = config('subscriptions.partner_plan_hierarchy', []);
        $currentLevel = $hierarchy[$currentTier] ?? 0;
        $tiers = config('subscriptions.partner_tiers', []);

        foreach ($hierarchy as $tierName => $level) {
            if ($level <= $currentLevel) {
                continue;
            }

            $currentLimit = config("subscriptions.partner_tiers.{$currentTier}.limits.{$meter}");
            $nextLimit = config("subscriptions.partner_tiers.{$tierName}.limits.{$meter}");

            if ($nextLimit === null || ($currentLimit !== null && $nextLimit > $currentLimit)) {
                return $tierName;
            }
        }

        return null;
    }

    /**
     * Build a standardized limit exceeded response for partners.
     */
    public function buildLimitExceededResponse(Partner $partner, string $meter): array
    {
        $currentTier = $this->getPartnerTier($partner);
        $upgradeTier = $this->getUpgradeTier($currentTier, $meter);
        $usage = $this->getUsage($partner, $meter);

        $meterNames = [
            'companies' => 'Companies',
            'ai_credits_per_month' => 'AI Credits',
            'bank_accounts' => 'Bank Accounts',
            'payroll_employees' => 'Payroll Employees',
            'efaktura_per_month' => 'E-Faktura',
            'documents_stored_per_month' => 'Documents',
            'client_portal_invites' => 'Client Portal Invites',
        ];

        $meterName = $meterNames[$meter] ?? $meter;

        return [
            'error' => 'partner_limit_exceeded',
            'message' => "You've reached your {$meterName} limit ({$usage['used']}/{$usage['limit']}). Upgrade to continue.",
            'meter' => $meter,
            'meter_name' => $meterName,
            'current_tier' => $currentTier,
            'upgrade_tier' => $upgradeTier,
            'usage' => $usage,
            'upgrade_url' => '/partner/billing',
            'is_trial' => $this->isOnTrial($partner),
            'trial_days_remaining' => $this->getTrialDaysRemaining($partner),
        ];
    }
}
// CLAUDE-CHECKPOINT
