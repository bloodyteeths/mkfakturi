<?php

namespace Modules\Mk\Partner\Services;

use App\Models\Company;
use App\Models\Partner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Portfolio Tier Recalculation Service
 *
 * Recalculates which non-paying portfolio companies get Standard vs Accountant Basic
 * features based on the 1:1 sliding scale (each paying company covers 1 non-paying).
 *
 * During grace period: all companies get Standard features.
 * After grace: paying companies keep their tier, non-paying get coverage based on ratio.
 */
class PortfolioTierService
{
    /**
     * Recalculate tier overrides for all companies in a partner's portfolio.
     */
    public function recalculate(Partner $partner): array
    {
        if (! $partner->portfolio_enabled) {
            return ['status' => 'portfolio_not_enabled'];
        }

        $coveredTier = config('subscriptions.portfolio.covered_tier', 'standard');
        $uncoveredTier = config('subscriptions.portfolio.uncovered_tier', 'accountant_basic');
        $coverageRatio = config('subscriptions.portfolio.coverage_ratio', 1);

        // Load all portfolio companies with their subscriptions
        $portfolioCompanies = $partner->portfolioCompanies()->with('subscription')->get();

        if ($portfolioCompanies->isEmpty()) {
            return ['status' => 'no_companies', 'total' => 0];
        }

        // Separate paying and non-paying companies
        $paying = $portfolioCompanies->filter(function ($company) {
            return $company->subscription
                && in_array($company->subscription->status, ['trial', 'active'])
                && $company->subscription->plan !== 'free';
        });

        $nonPaying = $portfolioCompanies->filter(function ($company) {
            return ! $company->subscription
                || ! in_array($company->subscription->status ?? '', ['trial', 'active'])
                || $company->subscription->plan === 'free';
        })->sortBy('created_at'); // Oldest first get covered

        $payingCount = $paying->count();
        $coveredSlots = (int) ($payingCount * $coverageRatio);
        $inGrace = $partner->isInGracePeriod();

        $stats = [
            'status' => 'recalculated',
            'total' => $portfolioCompanies->count(),
            'paying' => $payingCount,
            'non_paying' => $nonPaying->count(),
            'covered_slots' => $coveredSlots,
            'in_grace' => $inGrace,
        ];

        DB::beginTransaction();

        try {
            // Paying companies: their tier is determined by their own subscription
            // No need to set portfolio_tier_override for them
            foreach ($paying as $company) {
                $this->updateCompanyTierOverride($partner, $company, null);
            }

            // Non-paying companies: assign covered or uncovered tier
            foreach ($nonPaying->values() as $index => $company) {
                if ($inGrace) {
                    // During grace: all non-paying get covered tier
                    $tier = $coveredTier;
                } else {
                    // After grace: first N get covered, rest get uncovered
                    $tier = $index < $coveredSlots ? $coveredTier : $uncoveredTier;
                }

                $this->updateCompanyTierOverride($partner, $company, $tier);
            }

            DB::commit();

            Log::info('Portfolio tiers recalculated', [
                'partner_id' => $partner->id,
                'stats' => $stats,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Portfolio tier recalculation failed', [
                'partner_id' => $partner->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $stats;
    }

    /**
     * Recalculate tiers for all active portfolio partners.
     */
    public function recalculateAll(): int
    {
        $partners = Partner::where('portfolio_enabled', true)
            ->where('is_active', true)
            ->get();

        $count = 0;

        foreach ($partners as $partner) {
            $this->recalculate($partner);
            $count++;
        }

        return $count;
    }

    /**
     * Update the tier override for a specific company in the portfolio.
     */
    protected function updateCompanyTierOverride(Partner $partner, Company $company, ?string $tier): void
    {
        // Update partner_company_links
        DB::table('partner_company_links')
            ->where('partner_id', $partner->id)
            ->where('company_id', $company->id)
            ->where('is_portfolio_managed', true)
            ->update(['portfolio_tier_override' => $tier]);

        // Update companies.subscription_tier for fast lookups (only for non-paying)
        // Paying companies have their subscription_tier set by Stripe webhooks
        if ($tier !== null) {
            $company->update(['subscription_tier' => $tier]);
        }
    }
}
// CLAUDE-CHECKPOINT
