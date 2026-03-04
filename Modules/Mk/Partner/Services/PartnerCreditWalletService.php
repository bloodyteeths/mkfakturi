<?php

namespace Modules\Mk\Partner\Services;

use App\Models\AffiliateEvent;
use App\Models\Partner;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Partner Credit Wallet Service
 *
 * Calculates how partner commissions are allocated:
 * 1. Commission earned from paying companies (20%)
 * 2. Coverage cost for uncovered companies beyond 1:1 ratio
 * 3. Wallet covers additional companies (commission → coverage first)
 * 4. Surplus = payout to accountant
 *
 * The wallet is virtual — no separate balance table. It's calculated
 * on-the-fly from affiliate_events and portfolio state.
 */
class PartnerCreditWalletService
{
    /**
     * Calculate the full wallet state for a partner for a given month.
     *
     * @param  string|null  $monthRef  YYYY-MM format (defaults to current month)
     * @return array{
     *   commission_earned: float,
     *   coverage_cost_per_company: float,
     *   companies_after_1to1: int,
     *   wallet_covered_companies: int,
     *   still_uncovered: int,
     *   coverage_cost_total: float,
     *   surplus: float,
     *   in_grace: bool
     * }
     */
    public function calculateWallet(Partner $partner, ?string $monthRef = null): array
    {
        $monthRef = $monthRef ?? now()->format('Y-m');
        $inGrace = $partner->isInGracePeriod();

        // Get portfolio stats
        $total = $partner->portfolioCompanies()->count();
        $payingCount = $partner->getPayingCompanyCount();
        $nonPaying = $total - $payingCount;
        $coverageRatio = config('subscriptions.portfolio.coverage_ratio', 1);
        $coveredBy1to1 = (int) ($payingCount * $coverageRatio);

        // Companies still uncovered after 1:1
        $uncoveredAfter1to1 = max(0, $nonPaying - $coveredBy1to1);

        // Cost per company to cover at Standard tier (monthly EUR price)
        $coveredTier = config('subscriptions.portfolio.covered_tier', 'standard');
        $coverageCostPerCompany = (float) config("subscriptions.tiers.{$coveredTier}.price_monthly", 39.00);

        // Commission earned this month (only direct commissions, not upline/sales_rep)
        $commissionEarned = $this->getMonthlyCommission($partner, $monthRef);

        // How many additional companies can the commission cover?
        $walletCoveredCompanies = 0;
        $coverageCostTotal = 0.0;
        $surplus = $commissionEarned;

        if (! $inGrace && $uncoveredAfter1to1 > 0 && $commissionEarned > 0) {
            // Each uncovered company costs $coverageCostPerCompany to cover
            $walletCoveredCompanies = min(
                $uncoveredAfter1to1,
                (int) floor($commissionEarned / $coverageCostPerCompany)
            );
            $coverageCostTotal = $walletCoveredCompanies * $coverageCostPerCompany;
            $surplus = round($commissionEarned - $coverageCostTotal, 2);
        }

        $stillUncovered = $inGrace ? 0 : max(0, $uncoveredAfter1to1 - $walletCoveredCompanies);

        return [
            'month_ref' => $monthRef,
            'in_grace' => $inGrace,
            'total_companies' => $total,
            'paying_companies' => $payingCount,
            'non_paying_companies' => $nonPaying,
            'covered_by_1to1' => min($nonPaying, $coveredBy1to1),
            'companies_after_1to1' => $uncoveredAfter1to1,
            'commission_earned' => round($commissionEarned, 2),
            'coverage_cost_per_company' => $coverageCostPerCompany,
            'wallet_covered_companies' => $walletCoveredCompanies,
            'coverage_cost_total' => round($coverageCostTotal, 2),
            'still_uncovered' => $stillUncovered,
            'surplus' => max(0, $surplus),
        ];
    }

    /**
     * Get the total commission earned by a partner for a specific month.
     * Only counts direct commissions (not upline events or sales rep events).
     */
    public function getMonthlyCommission(Partner $partner, string $monthRef): float
    {
        return (float) AffiliateEvent::where('affiliate_partner_id', $partner->id)
            ->where('event_type', 'recurring_commission')
            ->where('month_ref', $monthRef)
            ->where('is_clawed_back', false)
            ->whereNull('metadata->type') // Exclude upline/sales_rep sub-events
            ->sum('amount');
    }

    /**
     * Get total unpaid commission that can be used for wallet coverage.
     * This is the running total of unpaid commissions (not just one month).
     */
    public function getUnpaidCommissionTotal(Partner $partner): float
    {
        return (float) AffiliateEvent::where('affiliate_partner_id', $partner->id)
            ->where('event_type', 'recurring_commission')
            ->where('is_clawed_back', false)
            ->whereNull('payout_id')
            ->whereNull('metadata->type') // Exclude upline/sales_rep sub-events
            ->sum('amount');
    }

    /**
     * Calculate the net payout amount after wallet deductions.
     * Used by PartnerPayoutService before creating a payout.
     *
     * @return array{gross_commission: float, coverage_deduction: float, net_payout: float, companies_covered: int}
     */
    public function calculateNetPayout(Partner $partner, float $grossCommission): array
    {
        $inGrace = $partner->isInGracePeriod();

        if ($inGrace) {
            // During grace, no deductions — but also no payout (commissions accumulate)
            return [
                'gross_commission' => $grossCommission,
                'coverage_deduction' => 0.0,
                'net_payout' => $grossCommission,
                'companies_covered' => 0,
            ];
        }

        // Calculate uncovered companies after 1:1
        $total = $partner->portfolioCompanies()->count();
        $payingCount = $partner->getPayingCompanyCount();
        $nonPaying = $total - $payingCount;
        $coverageRatio = config('subscriptions.portfolio.coverage_ratio', 1);
        $coveredBy1to1 = (int) ($payingCount * $coverageRatio);
        $uncoveredAfter1to1 = max(0, $nonPaying - $coveredBy1to1);

        if ($uncoveredAfter1to1 === 0) {
            // All companies covered by 1:1 — full commission is surplus
            return [
                'gross_commission' => $grossCommission,
                'coverage_deduction' => 0.0,
                'net_payout' => $grossCommission,
                'companies_covered' => 0,
            ];
        }

        // Calculate coverage cost
        $coveredTier = config('subscriptions.portfolio.covered_tier', 'standard');
        $costPerCompany = (float) config("subscriptions.tiers.{$coveredTier}.price_monthly", 39.00);

        // Only deduct for companies we can actually fully cover
        $companiesCovered = $costPerCompany > 0
            ? min($uncoveredAfter1to1, (int) floor($grossCommission / $costPerCompany))
            : 0;
        $coverageDeduction = $companiesCovered * $costPerCompany;
        $netPayout = max(0, round($grossCommission - $coverageDeduction, 2));

        return [
            'gross_commission' => round($grossCommission, 2),
            'coverage_deduction' => round($coverageDeduction, 2),
            'net_payout' => $netPayout,
            'companies_covered' => $companiesCovered,
        ];
    }

    /**
     * Get wallet forecast — what happens after grace ends.
     * Useful for showing projections during grace period.
     */
    public function getWalletForecast(Partner $partner): array
    {
        $stats = $partner->getPortfolioStats();
        $payingCount = $stats['paying'];
        $nonPaying = $stats['non_paying'];
        $coverageRatio = config('subscriptions.portfolio.coverage_ratio', 1);
        $coveredBy1to1 = (int) ($payingCount * $coverageRatio);
        $uncoveredAfter1to1 = max(0, $nonPaying - $coveredBy1to1);

        // Project monthly commission from paying companies
        $payingCompanies = $partner->portfolioCompanies()
            ->whereHas('subscription', function ($q) {
                $q->whereIn('status', ['trial', 'active'])
                    ->where('plan', '!=', 'free');
            })
            ->with('subscription')
            ->get();

        $monthlyRevenue = $payingCompanies->sum(fn ($c) => $c->subscription->price_monthly ?? 0);
        $commissionRate = $partner->getEffectiveCommissionRate();
        $projectedCommission = round($monthlyRevenue * $commissionRate, 2);

        // How many uncovered companies can projected commission cover?
        $coveredTier = config('subscriptions.portfolio.covered_tier', 'standard');
        $costPerCompany = (float) config("subscriptions.tiers.{$coveredTier}.price_monthly", 39.00);

        $walletCovers = 0;
        $projectedPayout = $projectedCommission;

        if ($uncoveredAfter1to1 > 0 && $projectedCommission > 0 && $costPerCompany > 0) {
            $walletCovers = min(
                $uncoveredAfter1to1,
                (int) floor($projectedCommission / $costPerCompany)
            );
            $coverageCost = $walletCovers * $costPerCompany;
            $projectedPayout = max(0, round($projectedCommission - $coverageCost, 2));
        }

        $stillUncovered = max(0, $uncoveredAfter1to1 - $walletCovers);

        return [
            'total_companies' => $stats['total'],
            'paying_companies' => $payingCount,
            'covered_by_1to1' => min($nonPaying, $coveredBy1to1),
            'uncovered_after_1to1' => $uncoveredAfter1to1,
            'monthly_revenue' => round($monthlyRevenue, 2),
            'commission_rate' => $commissionRate,
            'projected_commission' => $projectedCommission,
            'cost_per_company' => $costPerCompany,
            'wallet_covers' => $walletCovers,
            'still_uncovered' => $stillUncovered,
            'projected_payout' => $projectedPayout,
            'grace_ends_at' => $partner->portfolio_grace_ends_at?->toISOString(),
        ];
    }
}
// CLAUDE-CHECKPOINT
