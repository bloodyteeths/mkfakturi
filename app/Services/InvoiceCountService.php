<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * InvoiceCountService
 *
 * Manages invoice counting and limits for subscription tiers.
 * Implements caching for performance.
 */
class InvoiceCountService
{
    /**
     * Get the invoice count for a company in the current month
     */
    public function getMonthlyCount(int $companyId): int
    {
        $cacheEnabled = config('subscriptions.cache.enabled', true);

        if (! $cacheEnabled) {
            return $this->queryMonthlyCount($companyId);
        }

        $cacheKey = $this->getCacheKey($companyId);
        $cacheTtl = config('subscriptions.cache.ttl', 300);

        return Cache::remember($cacheKey, $cacheTtl, function () use ($companyId) {
            return $this->queryMonthlyCount($companyId);
        });
    }

    /**
     * Query the database for monthly invoice count
     */
    protected function queryMonthlyCount(int $companyId): int
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        return Invoice::where('company_id', $companyId)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();
    }

    /**
     * Get the invoice limit for a company's current plan
     *
     * @return int|null Null means unlimited
     */
    public function getInvoiceLimit(Company $company): ?int
    {
        // Load subscription if not already loaded
        if (! $company->relationLoaded('subscription')) {
            $company->load('subscription');
        }

        // No subscription or inactive = default to free tier
        if (! $company->subscription || ! $company->subscription->isActive()) {
            return config('subscriptions.tiers.free.invoice_limit', 5);
        }

        $plan = $company->subscription->plan;
        $limit = config("subscriptions.tiers.{$plan}.invoice_limit");

        // Null means unlimited
        return $limit;
    }

    /**
     * Check if company has reached their invoice limit
     */
    public function hasReachedLimit(Company $company): bool
    {
        $limit = $this->getInvoiceLimit($company);

        // Unlimited plan
        if ($limit === null) {
            return false;
        }

        $currentCount = $this->getMonthlyCount($company->id);

        return $currentCount >= $limit;
    }

    /**
     * Get remaining invoices for the current month
     *
     * @return int|null Null means unlimited
     */
    public function getRemainingCount(Company $company): ?int
    {
        $limit = $this->getInvoiceLimit($company);

        // Unlimited plan
        if ($limit === null) {
            return null;
        }

        $currentCount = $this->getMonthlyCount($company->id);

        return max(0, $limit - $currentCount);
    }

    /**
     * Get invoice usage statistics for a company
     */
    public function getUsageStats(Company $company): array
    {
        $limit = $this->getInvoiceLimit($company);
        $currentCount = $this->getMonthlyCount($company->id);
        $remaining = $this->getRemainingCount($company);

        return [
            'current_count' => $currentCount,
            'limit' => $limit,
            'remaining' => $remaining,
            'is_unlimited' => $limit === null,
            'has_reached_limit' => $this->hasReachedLimit($company),
            'usage_percentage' => $limit ? round(($currentCount / $limit) * 100, 2) : 0,
            'resets_at' => Carbon::now()->endOfMonth()->toIso8601String(),
        ];
    }

    /**
     * Clear the cache for a company's invoice count
     */
    public function clearCache(int $companyId): void
    {
        $cacheKey = $this->getCacheKey($companyId);
        Cache::forget($cacheKey);
    }

    /**
     * Increment the cached count (called after invoice creation)
     */
    public function incrementCache(int $companyId): void
    {
        $cacheEnabled = config('subscriptions.cache.enabled', true);

        if (! $cacheEnabled) {
            return;
        }

        $cacheKey = $this->getCacheKey($companyId);

        // If cache exists, increment it
        if (Cache::has($cacheKey)) {
            Cache::increment($cacheKey);
        } else {
            // Otherwise, set it to current count
            $cacheTtl = config('subscriptions.cache.ttl', 300);
            Cache::put($cacheKey, $this->queryMonthlyCount($companyId), $cacheTtl);
        }
    }

    /**
     * Reset monthly invoice counts for all companies
     * (Called on 1st of each month via scheduled job)
     *
     * @return int Number of cache entries cleared
     */
    public function resetMonthlyCounts(): int
    {
        // Clear all invoice count caches
        $prefix = config('subscriptions.cache.prefix', 'subscription:');
        $pattern = $prefix.'invoice_count:*';

        // Get all company IDs that have invoices
        $companyIds = Company::has('invoices')->pluck('id');

        $cleared = 0;
        foreach ($companyIds as $companyId) {
            $this->clearCache($companyId);
            $cleared++;
        }

        return $cleared;
    }

    /**
     * Get the upgrade message for a company that hit their limit
     */
    public function getUpgradeMessage(Company $company): string
    {
        // Load subscription if not already loaded
        if (! $company->relationLoaded('subscription')) {
            $company->load('subscription');
        }

        $plan = $company->subscription?->plan ?? 'free';

        return config("subscriptions.upgrade_messages.invoice_limit.{$plan}",
            'You\'ve reached your invoice limit. Please upgrade to continue creating invoices.');
    }

    /**
     * Get the Paddle price ID for the next tier upgrade
     */
    public function getUpgradePriceId(Company $company): ?string
    {
        // Load subscription if not already loaded
        if (! $company->relationLoaded('subscription')) {
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

        if (! $nextTier) {
            return null; // Already on highest tier
        }

        return config("subscriptions.paddle_prices.{$nextTier}");
    }

    /**
     * Get cache key for a company's invoice count
     */
    protected function getCacheKey(int $companyId): string
    {
        $prefix = config('subscriptions.cache.prefix', 'subscription:');
        $month = Carbon::now()->format('Y-m');

        return "{$prefix}invoice_count:{$companyId}:{$month}";
    }
}
// CLAUDE-CHECKPOINT
