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

        // Calculate commission
        $commissionRate = $this->calculateCommissionRate($partner);
        $directCommission = $subscriptionAmount * $commissionRate;

        $uplineCommission = null;
        $uplinePartnerId = null;

        // Check for multi-level commission (upline)
        if ($partner->user_id) {
            $user = User::find($partner->user_id);
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
                        'company_id' => $companyId,
                        'event_type' => 'recurring_commission',
                        'amount' => $uplineCommission,
                        'upline_amount' => null,
                        'month_ref' => $monthRef,
                        'subscription_id' => $subscriptionId,
                        'metadata' => [
                            'type' => 'upline',
                            'downline_partner_id' => $partner->id,
                        ],
                    ]);
                }
            }
        }

        // Create direct commission event
        $event = AffiliateEvent::create([
            'affiliate_partner_id' => $partner->id,
            'upline_partner_id' => $uplinePartnerId,
            'company_id' => $companyId,
            'event_type' => 'recurring_commission',
            'amount' => $directCommission,
            'upline_amount' => $uplineCommission,
            'month_ref' => $monthRef,
            'subscription_id' => $subscriptionId,
            'metadata' => [
                'subscription_amount' => $subscriptionAmount,
                'commission_rate' => $commissionRate,
            ],
        ]);

        Log::info('Recurring commission recorded', [
            'event_id' => $event->id,
            'partner_id' => $partner->id,
            'company_id' => $companyId,
            'amount' => $directCommission,
            'upline_amount' => $uplineCommission,
        ]);

        return [
            'success' => true,
            'event_id' => $event->id,
            'direct_commission' => $directCommission,
            'upline_commission' => $uplineCommission,
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
}

// CLAUDE-CHECKPOINT
