<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Usage Limit Service
 *
 * Tracks and enforces usage limits for subscription tiers.
 * Handles monthly limits (expenses, estimates, AI queries) and
 * total limits (custom fields, active recurring invoices).
 */
class UsageLimitService
{
    /**
     * Check if company can use a feature (within limits)
     *
     * @param  Company  $company
     * @param  string  $feature  Feature name (e.g., 'expenses_per_month', 'custom_fields')
     * @return bool
     */
    public function canUse(Company $company, string $feature): bool
    {
        $usage = $this->getUsage($company, $feature);

        // If limit is null (unlimited), return true
        if ($usage['limit'] === null) {
            return true;
        }

        // Check if remaining usage is available
        return $usage['remaining'] > 0;
    }

    /**
     * Get usage statistics for a feature
     *
     * @param  Company  $company
     * @param  string  $feature  Feature name
     * @return array ['used' => X, 'limit' => Y, 'remaining' => Z]
     */
    public function getUsage(Company $company, string $feature): array
    {
        $tier = $this->getCompanyTier($company);
        $limit = $this->getFeatureLimit($tier, $feature);

        $used = $this->getUsedCount($company, $feature);

        $remaining = $limit === null ? null : max(0, $limit - $used);

        return [
            'used' => $used,
            'limit' => $limit,
            'remaining' => $remaining,
        ];
    }

    /**
     * Increment usage counter for a feature
     *
     * @param  Company  $company
     * @param  string  $feature  Feature name
     * @return void
     */
    public function incrementUsage(Company $company, string $feature): void
    {
        // Determine if this is a monthly or total limit feature
        $isMonthlyFeature = $this->isMonthlyFeature($feature);
        $period = $isMonthlyFeature ? now()->format('Y-m') : 'total';

        // Try to find existing record
        $existing = DB::table('usage_tracking')
            ->where('company_id', $company->id)
            ->where('feature', $feature)
            ->where('period', $period)
            ->first();

        if ($existing) {
            // Update existing record
            DB::table('usage_tracking')
                ->where('company_id', $company->id)
                ->where('feature', $feature)
                ->where('period', $period)
                ->increment('count');
        } else {
            // Insert new record
            DB::table('usage_tracking')->insert([
                'company_id' => $company->id,
                'feature' => $feature,
                'period' => $period,
                'count' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Log::debug('Usage incremented', [
            'company_id' => $company->id,
            'feature' => $feature,
            'period' => $period,
        ]);
    }

    /**
     * Get the company's current subscription tier
     *
     * @param  Company  $company
     * @return string Tier name ('free', 'starter', 'standard', 'business', 'max')
     */
    public function getCompanyTier(Company $company): string
    {
        // Check if company has an active subscription
        if (! $company->relationLoaded('subscription')) {
            $company->load('subscription');
        }

        $subscription = $company->subscription;

        // If no subscription or inactive, return 'free'
        if (! $subscription || ! in_array($subscription->status, ['trial', 'active'])) {
            return 'free';
        }

        return $subscription->plan ?? 'free';
    }

    /**
     * Get the limit for a specific feature in a tier
     *
     * @param  string  $tier
     * @param  string  $feature
     * @return int|null Limit value or null for unlimited
     */
    protected function getFeatureLimit(string $tier, string $feature): ?int
    {
        $config = config("subscriptions.tiers.{$tier}.limits.{$feature}");

        return $config;
    }

    /**
     * Get the current used count for a feature
     *
     * @param  Company  $company
     * @param  string  $feature
     * @return int
     */
    protected function getUsedCount(Company $company, string $feature): int
    {
        $isMonthlyFeature = $this->isMonthlyFeature($feature);

        if ($isMonthlyFeature) {
            $period = now()->format('Y-m');

            $record = DB::table('usage_tracking')
                ->where('company_id', $company->id)
                ->where('feature', $feature)
                ->where('period', $period)
                ->first();

            return $record ? (int) $record->count : 0;
        } else {
            // For total limits, we need to count actual records
            // This provides a real-time count from the database
            return $this->countActualUsage($company, $feature);
        }
    }

    /**
     * Count actual usage from database for non-monthly features
     *
     * @param  Company  $company
     * @param  string  $feature
     * @return int
     */
    protected function countActualUsage(Company $company, string $feature): int
    {
        switch ($feature) {
            case 'custom_fields':
                return $company->customFields()->count();

            case 'recurring_invoices_active':
                return $company->recurringInvoices()
                    ->where('status', 'ACTIVE')
                    ->count();

            default:
                // Fallback to usage_tracking table with 'total' period
                $record = DB::table('usage_tracking')
                    ->where('company_id', $company->id)
                    ->where('feature', $feature)
                    ->where('period', 'total')
                    ->first();

                return $record ? (int) $record->count : 0;
        }
    }

    /**
     * Check if a feature has monthly limits
     *
     * @param  string  $feature
     * @return bool
     */
    protected function isMonthlyFeature(string $feature): bool
    {
        $monthlyFeatures = [
            'expenses_per_month',
            'estimates_per_month',
            'ai_queries_per_month',
        ];

        return in_array($feature, $monthlyFeatures);
    }

    /**
     * Reset monthly usage counters (called by scheduled job)
     * This is typically run on the 1st of each month
     *
     * @return int Number of records reset
     */
    public function resetMonthlyUsage(): int
    {
        $previousMonth = now()->subMonth()->format('Y-m');

        // We don't actually delete old records for historical tracking
        // Just ensure new month starts fresh (records created on first increment)
        Log::info('Monthly usage reset completed', [
            'previous_month' => $previousMonth,
            'current_month' => now()->format('Y-m'),
        ]);

        return 0; // No records deleted, just logging
    }

    /**
     * Decrement usage counter for a feature (e.g., when deleting)
     *
     * @param  Company  $company
     * @param  string  $feature
     * @return void
     */
    public function decrementUsage(Company $company, string $feature): void
    {
        $isMonthlyFeature = $this->isMonthlyFeature($feature);

        if ($isMonthlyFeature) {
            $period = now()->format('Y-m');

            DB::table('usage_tracking')
                ->where('company_id', $company->id)
                ->where('feature', $feature)
                ->where('period', $period)
                ->where('count', '>', 0)
                ->decrement('count');
        } else {
            DB::table('usage_tracking')
                ->where('company_id', $company->id)
                ->where('feature', $feature)
                ->where('period', 'total')
                ->where('count', '>', 0)
                ->decrement('count');
        }

        Log::debug('Usage decremented', [
            'company_id' => $company->id,
            'feature' => $feature,
        ]);
    }

    /**
     * Get all usage statistics for a company
     *
     * @param  Company  $company
     * @return array
     */
    public function getAllUsage(Company $company): array
    {
        $features = [
            'expenses_per_month',
            'custom_fields',
            'recurring_invoices_active',
            'estimates_per_month',
            'ai_queries_per_month',
        ];

        $usage = [];

        foreach ($features as $feature) {
            $usage[$feature] = $this->getUsage($company, $feature);
        }

        return $usage;
    }
}
// CLAUDE-CHECKPOINT
