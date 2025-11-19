<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * UserCountService
 *
 * Manages user counting and limits for subscription tiers.
 * Similar to InvoiceCountService but for user management.
 */
class UserCountService
{
    /**
     * Get the user count for a company
     */
    public function getUserCount(int $companyId): int
    {
        $cacheEnabled = config('subscriptions.cache.enabled', true);

        if (!$cacheEnabled) {
            return $this->queryUserCount($companyId);
        }

        $cacheKey = $this->getCacheKey($companyId);
        $cacheTtl = config('subscriptions.cache.ttl', 300);

        return Cache::remember($cacheKey, $cacheTtl, function () use ($companyId) {
            return $this->queryUserCount($companyId);
        });
    }

    /**
     * Query the database for user count
     */
    protected function queryUserCount(int $companyId): int
    {
        // Count all users associated with this company
        // Including owner and invited users
        return DB::table('user_company')
            ->where('company_id', $companyId)
            ->count();
    }

    /**
     * Get the user limit for a company's current plan
     *
     * @return int|null Null means unlimited
     */
    public function getUserLimit(Company $company): ?int
    {
        // Load subscription if not already loaded
        if (!$company->relationLoaded('subscription')) {
            $company->load('subscription');
        }

        // No subscription or inactive = default to free tier
        if (!$company->subscription || !$company->subscription->isActive()) {
            return config('subscriptions.tiers.free.users', 1);
        }

        $plan = $company->subscription->plan;
        $limit = config("subscriptions.tiers.{$plan}.users");

        // Null means unlimited
        return $limit;
    }

    /**
     * Check if company has reached their user limit
     */
    public function hasReachedLimit(Company $company): bool
    {
        $limit = $this->getUserLimit($company);

        // Unlimited plan
        if ($limit === null) {
            return false;
        }

        $currentCount = $this->getUserCount($company->id);

        return $currentCount >= $limit;
    }

    /**
     * Get remaining user slots
     *
     * @return int|null Null means unlimited
     */
    public function getRemainingCount(Company $company): ?int
    {
        $limit = $this->getUserLimit($company);

        // Unlimited plan
        if ($limit === null) {
            return null;
        }

        $currentCount = $this->getUserCount($company->id);

        return max(0, $limit - $currentCount);
    }

    /**
     * Get user usage statistics for a company
     */
    public function getUsageStats(Company $company): array
    {
        $limit = $this->getUserLimit($company);
        $currentCount = $this->getUserCount($company->id);
        $remaining = $this->getRemainingCount($company);

        return [
            'current_count' => $currentCount,
            'limit' => $limit,
            'remaining' => $remaining,
            'is_unlimited' => $limit === null,
            'has_reached_limit' => $this->hasReachedLimit($company),
            'usage_percentage' => $limit ? round(($currentCount / $limit) * 100, 2) : 0,
        ];
    }

    /**
     * Clear the cache for a company's user count
     */
    public function clearCache(int $companyId): void
    {
        $cacheKey = $this->getCacheKey($companyId);
        Cache::forget($cacheKey);
    }

    /**
     * Increment the cached count (called after user creation)
     */
    public function incrementCache(int $companyId): void
    {
        $cacheEnabled = config('subscriptions.cache.enabled', true);

        if (!$cacheEnabled) {
            return;
        }

        $cacheKey = $this->getCacheKey($companyId);

        // If cache exists, increment it
        if (Cache::has($cacheKey)) {
            Cache::increment($cacheKey);
        } else {
            // Otherwise, set it to current count
            $cacheTtl = config('subscriptions.cache.ttl', 300);
            Cache::put($cacheKey, $this->queryUserCount($companyId), $cacheTtl);
        }
    }

    /**
     * Decrement the cached count (called after user deletion)
     */
    public function decrementCache(int $companyId): void
    {
        $cacheEnabled = config('subscriptions.cache.enabled', true);

        if (!$cacheEnabled) {
            return;
        }

        $cacheKey = $this->getCacheKey($companyId);

        // If cache exists, decrement it
        if (Cache::has($cacheKey)) {
            Cache::decrement($cacheKey);
        }
    }

    /**
     * Get the upgrade message for a company that hit their limit
     */
    public function getUpgradeMessage(Company $company): string
    {
        // Load subscription if not already loaded
        if (!$company->relationLoaded('subscription')) {
            $company->load('subscription');
        }

        $plan = $company->subscription?->plan ?? 'free';

        return config(
            "subscriptions.upgrade_messages.user_limit.{$plan}",
            'You\'ve reached your user limit. Please upgrade to invite more users.'
        );
    }

    /**
     * Get the Paddle price ID for the next tier upgrade
     */
    public function getUpgradePriceId(Company $company): ?string
    {
        // Load subscription if not already loaded
        if (!$company->relationLoaded('subscription')) {
            $company->load('subscription');
        }

        $currentPlan = $company->subscription?->plan ?? 'free';
        $planHierarchy = config('subscriptions.plan_hierarchy');

        // Find next tier
        $currentLevel = $planHierarchy[$currentPlan] ?? 0;
        $nextTier = null;

        foreach ($planHierarchy as $plan => $level) {
            if ($level === $currentLevel + 1) {
                $nextTier = $plan;
                break;
            }
        }

        if (!$nextTier) {
            return null; // Already on highest tier
        }

        return config("subscriptions.paddle_prices.{$nextTier}");
    }

    /**
     * Get cache key for a company's user count
     */
    protected function getCacheKey(int $companyId): string
    {
        $prefix = config('subscriptions.cache.prefix', 'subscription:');

        return "{$prefix}user_count:{$companyId}";
    }
}
// CLAUDE-CHECKPOINT
