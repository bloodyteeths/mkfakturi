<?php

namespace Modules\Mk\Public\Services;

use App\Models\Company;
use App\Models\CompanyReferral;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;

/**
 * CompanyReferralRewardService
 *
 * Handles Stripe coupon creation and application for company-to-company referrals.
 * Invitee gets 10% off first payment, Inviter gets 10% off next billing.
 */
class CompanyReferralRewardService
{
    /**
     * Discount percentages (configurable via env)
     */
    protected int $inviteeDiscount;

    protected int $inviterReward;

    protected int $couponExpiryMonths;

    public function __construct()
    {
        $this->inviteeDiscount = (int) config('affiliate.company_referral.invitee_discount_percent', 10);
        $this->inviterReward = (int) config('affiliate.company_referral.inviter_reward_percent', 10);
        $this->couponExpiryMonths = (int) config('affiliate.company_referral.coupon_expiry_months', 12);

        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a 10% off coupon for the invitee to use at checkout.
     *
     * @return string|null Stripe coupon ID or null on failure
     */
    public function createInviteeCoupon(CompanyReferral $referral): ?string
    {
        try {
            $coupon = \Stripe\Coupon::create([
                'percent_off' => $this->inviteeDiscount,
                'duration' => 'once',
                'name' => 'Referral Welcome - '.$this->inviteeDiscount.'% Off',
                'max_redemptions' => 1,
                'redeem_by' => strtotime('+'.$this->couponExpiryMonths.' months'),
                'metadata' => [
                    'referral_id' => $referral->id,
                    'type' => 'invitee_discount',
                    'inviter_company_id' => $referral->inviter_company_id,
                ],
            ]);

            Log::info('Created invitee referral coupon', [
                'referral_id' => $referral->id,
                'coupon_id' => $coupon->id,
                'percent_off' => $this->inviteeDiscount,
            ]);

            return $coupon->id;
        } catch (\Exception $e) {
            Log::error('Failed to create invitee coupon', [
                'referral_id' => $referral->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Create a 10% off coupon for the inviter as a reward.
     * Called after invitee completes their first paid checkout.
     *
     * @return string|null Stripe coupon ID or null on failure
     */
    public function createInviterRewardCoupon(CompanyReferral $referral): ?string
    {
        try {
            $coupon = \Stripe\Coupon::create([
                'percent_off' => $this->inviterReward,
                'duration' => 'once',
                'name' => 'Referral Thank You - '.$this->inviterReward.'% Off',
                'max_redemptions' => 1,
                'redeem_by' => strtotime('+'.$this->couponExpiryMonths.' months'),
                'metadata' => [
                    'referral_id' => $referral->id,
                    'type' => 'inviter_reward',
                    'invitee_company_id' => $referral->invitee_company_id,
                ],
            ]);

            Log::info('Created inviter reward coupon', [
                'referral_id' => $referral->id,
                'coupon_id' => $coupon->id,
                'percent_off' => $this->inviterReward,
            ]);

            return $coupon->id;
        } catch (\Exception $e) {
            Log::error('Failed to create inviter reward coupon', [
                'referral_id' => $referral->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Apply the inviter reward coupon to their Stripe customer.
     * The coupon will be applied to their next invoice.
     */
    public function applyInviterCoupon(Company $inviterCompany, string $couponId): bool
    {
        try {
            // Company must have a Stripe customer ID
            if (empty($inviterCompany->stripe_id)) {
                Log::warning('Cannot apply inviter coupon - no Stripe customer', [
                    'company_id' => $inviterCompany->id,
                    'coupon_id' => $couponId,
                ]);

                return false;
            }

            // Apply coupon to customer (will apply to next invoice)
            \Stripe\Customer::update($inviterCompany->stripe_id, [
                'coupon' => $couponId,
            ]);

            Log::info('Applied inviter reward coupon to customer', [
                'company_id' => $inviterCompany->id,
                'stripe_customer_id' => $inviterCompany->stripe_id,
                'coupon_id' => $couponId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to apply inviter coupon', [
                'company_id' => $inviterCompany->id,
                'coupon_id' => $couponId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Process inviter reward after invitee completes checkout.
     * Creates coupon and applies it to inviter's account.
     */
    public function processInviterReward(CompanyReferral $referral): bool
    {
        // Don't process if already rewarded
        if ($referral->referral_reward_status === 'both_rewarded') {
            return false;
        }

        // Get inviter company
        $inviterCompany = $referral->inviterCompany;
        if (! $inviterCompany) {
            Log::warning('Inviter company not found for referral reward', [
                'referral_id' => $referral->id,
            ]);

            return false;
        }

        // Create the reward coupon
        $couponId = $this->createInviterRewardCoupon($referral);
        if (! $couponId) {
            return false;
        }

        // Try to apply immediately if inviter has Stripe customer
        $applied = $this->applyInviterCoupon($inviterCompany, $couponId);

        // Mark referral as fully rewarded
        $referral->markInviterRewarded($couponId);

        if (! $applied) {
            Log::info('Inviter coupon created but not applied - will apply when customer created', [
                'referral_id' => $referral->id,
                'coupon_id' => $couponId,
            ]);
        }

        return true;
    }

    /**
     * Validate a company referral token.
     *
     * @return array|null Referral data or null if invalid
     */
    public function validateReferralToken(string $token): ?array
    {
        $referral = CompanyReferral::where('referral_token', $token)
            ->where('status', 'pending')
            ->with('inviterCompany')
            ->first();

        if (! $referral || ! $referral->inviterCompany) {
            return null;
        }

        return [
            'referral_id' => $referral->id,
            'inviter_company_id' => $referral->inviter_company_id,
            'inviter_company_name' => $referral->inviterCompany->name,
            'discount_percent' => $this->inviteeDiscount,
        ];
    }
}

// CLAUDE-CHECKPOINT
