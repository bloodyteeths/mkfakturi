<?php

namespace App\Services;

use App\Models\AffiliateEvent;
use App\Models\AffiliateLink;
use App\Models\Company;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CommissionService
{
    /**
     * Record recurring commission from a company subscription payment
     *
     * @param int $companyId
     * @param float $subscriptionAmount
     * @param string $monthRef Format: YYYY-MM
     * @param string|null $subscriptionId
     * @return array
     */
    public function recordRecurring(int $companyId, float $subscriptionAmount, string $monthRef, ?string $subscriptionId = null): array
    {
        $company = Company::findOrFail($companyId);

        // Find the accountant/partner linked to this company
        $partnerCompanyLink = DB::table('partner_company_links')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->first();

        if (!$partnerCompanyLink) {
            Log::warning('No active partner link found for company', ['company_id' => $companyId]);
            return ['success' => false, 'message' => 'No partner linked to company'];
        }

        $partner = Partner::find($partnerCompanyLink->partner_id);
        if (!$partner || !$partner->is_active) {
            Log::warning('Partner not active', ['partner_id' => $partnerCompanyLink->partner_id]);
            return ['success' => false, 'message' => 'Partner not active'];
        }

        // Check if event already exists for this month
        $existing = AffiliateEvent::where('company_id', $companyId)
            ->where('event_type', 'recurring_commission')
            ->where('month_ref', $monthRef)
            ->first();

        if ($existing) {
            Log::info('Commission already recorded for this month', [
                'company_id' => $companyId,
                'month_ref' => $monthRef,
                'event_id' => $existing->id,
            ]);
            return ['success' => false, 'message' => 'Commission already recorded'];
        }

        // Calculate commission with multi-level logic
        $commissionRate = $this->calculateCommissionRate($partner);
        $directCommission = $subscriptionAmount * $commissionRate;

        $uplineCommission = null;
        $uplinePartnerId = null;
        $salesRepCommission = null;
        $salesRepId = null;

        // Get user for multi-level checks
        $user = $partner->user_id ? User::find($partner->user_id) : null;

        // Check for upline commission
        if ($user && $user->referrer_user_id) {
            // Find upline partner
            $uplinePartner = Partner::where('user_id', $user->referrer_user_id)
                ->where('is_active', true)
                ->first();

            if ($uplinePartner) {
                $uplineRate = config('affiliate.upline_rate', 0.05);
                $uplineCommission = $subscriptionAmount * $uplineRate;
                $uplinePartnerId = $uplinePartner->id;

                // Adjust direct commission for multi-level split
                $directRate = config('affiliate.direct_rate_multi_level', 0.15);
                $directCommission = $subscriptionAmount * $directRate;

                // Create upline event
                AffiliateEvent::create([
                    'affiliate_partner_id' => $uplinePartnerId,
                    'upline_partner_id' => null,
                    'sales_rep_id' => null,
                    'company_id' => $companyId,
                    'event_type' => 'recurring_commission',
                    'amount' => $uplineCommission,
                    'upline_amount' => null,
                    'sales_rep_amount' => null,
                    'month_ref' => $monthRef,
                    'subscription_id' => $subscriptionId,
                    'metadata' => [
                        'type' => 'upline',
                        'downline_partner_id' => $partner->id,
                    ],
                ]);
            }
        }

        // Check for sales rep commission
        if ($user && $user->sales_rep_id) {
            $salesRepRate = config('affiliate.sales_rep_rate', 0.05);
            $salesRepCommission = $subscriptionAmount * $salesRepRate;
            $salesRepId = $user->sales_rep_id;

            // Create sales rep event
            AffiliateEvent::create([
                'affiliate_partner_id' => $partner->id, // Link to accountant's partner record for payout
                'upline_partner_id' => null,
                'sales_rep_id' => $salesRepId,
                'company_id' => $companyId,
                'event_type' => 'recurring_commission',
                'amount' => $salesRepCommission,
                'upline_amount' => null,
                'sales_rep_amount' => null, // This IS the sales rep commission
                'month_ref' => $monthRef,
                'subscription_id' => $subscriptionId,
                'metadata' => [
                    'type' => 'sales_rep',
                    'accountant_partner_id' => $partner->id,
                    'accountant_user_id' => $user->id,
                ],
            ]);
        }

        // Round all commission amounts to 2 decimal places for currency safety
        $directCommission = round($directCommission, 2);
        if ($uplineCommission !== null) {
            $uplineCommission = round($uplineCommission, 2);
        }
        if ($salesRepCommission !== null) {
            $salesRepCommission = round($salesRepCommission, 2);
        }

        // Create direct commission event
        $event = AffiliateEvent::create([
            'affiliate_partner_id' => $partner->id,
            'upline_partner_id' => $uplinePartnerId,
            // Sales rep commission is tracked in separate events; keep this null to avoid double counting
            'sales_rep_id' => null,
            'company_id' => $companyId,
            'event_type' => 'recurring_commission',
            'amount' => $directCommission,
            'upline_amount' => $uplineCommission,
            'sales_rep_amount' => $salesRepCommission,
            'month_ref' => $monthRef,
            'subscription_id' => $subscriptionId,
            'metadata' => [
                'subscription_amount' => $subscriptionAmount,
                'commission_rate' => $commissionRate,
                'split_type' => $this->getCommissionSplitType($uplineCommission, $salesRepCommission),
            ],
        ]);

        Log::info('Recurring commission recorded', [
            'event_id' => $event->id,
            'partner_id' => $partner->id,
            'company_id' => $companyId,
            'amount' => $directCommission,
            'upline_amount' => $uplineCommission,
            'sales_rep_amount' => $salesRepCommission,
            'split_type' => $this->getCommissionSplitType($uplineCommission, $salesRepCommission),
        ]);

        return [
            'success' => true,
            'event_id' => $event->id,
            'direct_commission' => $directCommission,
            'upline_commission' => $uplineCommission,
            'sales_rep_commission' => $salesRepCommission,
        ];
    }

    /**
     * Record company signup bounty
     *
     * @param Company $company
     * @return array
     */
    public function recordCompanyBounty(Company $company): array
    {
        $partnerCompanyLink = DB::table('partner_company_links')
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->first();

        if (!$partnerCompanyLink) {
            return ['success' => false, 'message' => 'No partner linked'];
        }

        $partner = Partner::find($partnerCompanyLink->partner_id);
        if (!$partner || !$partner->is_active) {
            return ['success' => false, 'message' => 'Partner not active'];
        }

        // Check if bounty already recorded
        $existing = AffiliateEvent::where('company_id', $company->id)
            ->where('event_type', 'company_bounty')
            ->first();

        if ($existing) {
            return ['success' => false, 'message' => 'Bounty already recorded'];
        }

        $bountyAmount = config('affiliate.company_bounty', 50.00);

        $event = AffiliateEvent::create([
            'affiliate_partner_id' => $partner->id,
            'company_id' => $company->id,
            'event_type' => 'company_bounty',
            'amount' => $bountyAmount,
            'metadata' => [
                'company_name' => $company->name,
                'recorded_at' => now()->toIso8601String(),
            ],
        ]);

        // Increment conversion counter on affiliate link
        if ($affiliateLink = $this->findAffiliateLink($partner, $company)) {
            $affiliateLink->recordConversion();
        }

        Log::info('Company bounty recorded', [
            'event_id' => $event->id,
            'partner_id' => $partner->id,
            'company_id' => $company->id,
            'amount' => $bountyAmount,
        ]);

        return [
            'success' => true,
            'event_id' => $event->id,
            'amount' => $bountyAmount,
        ];
    }

    /**
     * Record partner activation bounty (€300)
     *
     * @param Partner $partner
     * @return array
     */
    public function recordPartnerBounty(Partner $partner): array
    {
        // Check eligibility
        if (!$this->isPartnerEligibleForBounty($partner)) {
            return ['success' => false, 'message' => 'Partner not eligible for bounty'];
        }

        // Check if bounty already recorded
        $existing = AffiliateEvent::where('affiliate_partner_id', $partner->id)
            ->where('event_type', 'partner_bounty')
            ->first();

        if ($existing) {
            return ['success' => false, 'message' => 'Bounty already recorded'];
        }

        $bountyAmount = config('affiliate.partner_bounty', 300.00);

        $event = AffiliateEvent::create([
            'affiliate_partner_id' => $partner->id,
            'company_id' => $partner->companies()->first()?->id, // Link to first company if available
            'event_type' => 'partner_bounty',
            'amount' => $bountyAmount,
            'metadata' => [
                'partner_name' => $partner->name,
                'active_companies' => $partner->activeCompanies()->count(),
                'recorded_at' => now()->toIso8601String(),
            ],
        ]);

        Log::info('Partner bounty recorded', [
            'event_id' => $event->id,
            'partner_id' => $partner->id,
            'amount' => $bountyAmount,
        ]);

        return [
            'success' => true,
            'event_id' => $event->id,
            'amount' => $bountyAmount,
        ];
    }

    /**
     * Calculate commission rate for a partner
     *
     * @param Partner $partner
     * @return float
     */
    public function calculateCommissionRate(Partner $partner): float
    {
        return $partner->getEffectiveCommissionRate();
    }

    /**
     * Check if partner is eligible for activation bounty
     *
     * @param Partner $partner
     * @return bool
     */
    public function isPartnerEligibleForBounty(Partner $partner): bool
    {
        // Check KYC verification if required
        $requiresKyc = config('affiliate.bounty_requires_kyc', true);
        if ($requiresKyc) {
            // Check KYC status (assuming a kyc_verified field or similar)
            // For now, we'll assume it's in metadata or a dedicated field
            // This would need to be implemented based on your KYC system
        }

        // Check minimum companies requirement
        $minCompanies = config('affiliate.bounty_min_companies', 3);
        $activeCompaniesCount = $partner->activeCompanies()->count();

        if ($activeCompaniesCount >= $minCompanies) {
            return true;
        }

        // Check minimum days requirement (alternative to min companies)
        $minDays = config('affiliate.bounty_min_days', 30);
        $daysSinceSignup = Carbon::parse($partner->created_at)->diffInDays(now());

        if ($daysSinceSignup >= $minDays) {
            return true;
        }

        return false;
    }

    /**
     * Handle refund/clawback scenario
     *
     * @param int $companyId
     * @param string $monthRef
     * @param string|null $reason
     * @return array
     */
    public function handleRefund(int $companyId, string $monthRef, ?string $reason = null): array
    {
        $events = AffiliateEvent::where('company_id', $companyId)
            ->where('event_type', 'recurring_commission')
            ->where('month_ref', $monthRef)
            ->where('is_clawed_back', false)
            ->get();

        if ($events->isEmpty()) {
            return ['success' => false, 'message' => 'No events found to claw back'];
        }

        $clawedBackCount = 0;
        foreach ($events as $event) {
            $event->clawback($reason);
            $clawedBackCount++;
        }

        Log::info('Commissions clawed back', [
            'company_id' => $companyId,
            'month_ref' => $monthRef,
            'events_count' => $clawedBackCount,
            'reason' => $reason,
        ]);

        return [
            'success' => true,
            'clawed_back_count' => $clawedBackCount,
        ];
    }

    /**
     * Find the affiliate link used for a company signup
     *
     * @param Partner $partner
     * @param Company $company
     * @return AffiliateLink|null
     */
    protected function findAffiliateLink(Partner $partner, Company $company): ?AffiliateLink
    {
        // Try to find affiliate link from user's session or metadata
        // This is a simplified version - you may want to store this during registration
        return $partner->affiliateLinks()
            ->where('target', 'company')
            ->where('is_active', true)
            ->first();
    }

    /**
     * Determine commission split type for metadata/reporting
     *
     * @param float|null $uplineCommission
     * @param float|null $salesRepCommission
     * @return string
     */
    protected function getCommissionSplitType(?float $uplineCommission, ?float $salesRepCommission): string
    {
        if ($uplineCommission && $salesRepCommission) {
            return '3-way'; // 15% direct + 5% upline + 5% sales rep = 25% total
        } elseif ($uplineCommission) {
            return '2-way_upline'; // 15% direct + 5% upline = 20% total
        } elseif ($salesRepCommission) {
            return '2-way_sales_rep'; // 15% direct + 5% sales rep = 20% total
        } else {
            return 'direct_only'; // 20% direct (or 22% for Plus)
        }
    }
}

// CLAUDE-CHECKPOINT
