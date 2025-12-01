<?php

/**
 * Stripe Connect Controller for Partner Payouts
 *
 * This controller handles Stripe Connect integration for paying Macedonian
 * partners their referral commissions via cross-border payouts.
 *
 * Flow:
 * 1. Partner clicks "Connect with Stripe" in Partner Dashboard
 * 2. System creates connected account with controller properties
 * 3. Partner completes onboarding via Stripe-hosted Account Links
 * 4. Monthly payouts are transferred to their connected account in EUR
 *
 * IMPORTANT: Requires these environment variables:
 *   STRIPE_SECRET=sk_live_xxx
 *   STRIPE_CONNECT_CLIENT_ID=ca_xxx (from Dashboard → Settings → Connect)
 *
 * @see https://docs.stripe.com/connect/cross-border-payouts
 */

namespace Modules\Mk\Partner\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class StripeConnectController extends Controller
{
    protected ?StripeClient $stripe = null;

    public function __construct()
    {
        // PLACEHOLDER: Ensure STRIPE_SECRET is set in your .env file
        $secretKey = config('services.stripe.secret');

        if (! empty($secretKey)) {
            $this->stripe = new StripeClient([
                'api_key' => $secretKey,
                'stripe_version' => '2025-11-17.clover', // Latest API version
            ]);
        }
    }

    /**
     * Create a Connected Account for a partner
     *
     * Uses controller properties (NOT legacy 'type' parameter):
     * - fees.payer = 'application' → Platform collects fees
     * - losses.payments = 'application' → Platform handles disputes
     * - stripe_dashboard.type = 'express' → Partner gets Express Dashboard
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function createConnectedAccount(Request $request)
    {
        if (! $this->stripe) {
            return response()->json([
                'error' => 'Stripe is not configured. Please contact support.',
            ], 500);
        }

        $partner = $request->user()->partner;

        if (! $partner) {
            return response()->json(['error' => 'Partner account not found'], 404);
        }

        if ($partner->stripe_account_id) {
            return response()->json([
                'error' => 'Stripe account already connected',
                'account_id' => $partner->stripe_account_id,
            ], 400);
        }

        try {
            /**
             * Create Connected Account with Controller Properties
             *
             * IMPORTANT: We use 'controller' object, NOT legacy 'type' parameter
             *
             * Controller properties:
             * - fees.payer = 'application' → Facturino collects subscription fees
             * - losses.payments = 'application' → Facturino handles any disputes
             * - stripe_dashboard.type = 'express' → Partner can view their balance/payouts
             */
            $account = $this->stripe->accounts->create([
                // Pre-fill from partner/user data
                'email' => $request->user()->email,
                'country' => 'MK', // Macedonia - supported for cross-border payouts
                'default_currency' => 'eur', // Cross-border payouts must be in EUR

                // Controller properties define the platform's business model
                // DO NOT use 'type' at top level - use 'controller' instead
                'controller' => [
                    // Platform (Facturino) is responsible for pricing and fee collection
                    'fees' => [
                        'payer' => 'application',
                    ],
                    // Platform is responsible for losses (refunds, chargebacks)
                    'losses' => [
                        'payments' => 'application',
                    ],
                    // Give partner access to Express Dashboard to view payouts
                    'stripe_dashboard' => [
                        'type' => 'express',
                    ],
                ],

                // Request transfer capability for receiving payouts
                'capabilities' => [
                    'transfers' => ['requested' => true],
                ],

                // REQUIRED for Macedonia (MK): recipient service agreement
                // See: https://stripe.com/docs/connect/service-agreement-types
                'tos_acceptance' => [
                    'service_agreement' => 'recipient',
                ],

                // Metadata for tracking
                'metadata' => [
                    'partner_id' => $partner->id,
                    'facturino_user_id' => $request->user()->id,
                    'type' => 'partner_payout_account',
                ],
            ]);

            // Update partner with Stripe account ID
            $partner->update([
                'stripe_account_id' => $account->id,
                'stripe_account_status' => 'pending', // Needs onboarding
                'payment_method' => 'stripe_connect',
            ]);

            Log::info('Stripe Connect account created for partner', [
                'partner_id' => $partner->id,
                'stripe_account_id' => $account->id,
            ]);

            return response()->json([
                'success' => true,
                'account_id' => $account->id,
                'status' => 'pending',
                'message' => 'Account created. Please complete onboarding.',
            ]);

        } catch (ApiErrorException $e) {
            Log::error('Failed to create Stripe Connect account', [
                'partner_id' => $partner->id,
                'error' => $e->getMessage(),
                'stripe_code' => $e->getStripeCode(),
            ]);

            return response()->json([
                'error' => 'Failed to create Stripe account: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Generate Account Link for Stripe-hosted onboarding
     *
     * Account Links are secure, time-limited URLs that redirect partners
     * to Stripe's hosted onboarding forms to complete:
     * - Identity verification (KYC)
     * - Bank account setup
     * - Terms of Service acceptance
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function createAccountLink(Request $request)
    {
        if (! $this->stripe) {
            return response()->json([
                'error' => 'Stripe is not configured',
            ], 500);
        }

        $partner = $request->user()->partner;

        if (! $partner || ! $partner->stripe_account_id) {
            return response()->json([
                'error' => 'No connected account found. Please create one first.',
            ], 400);
        }

        try {
            /**
             * Create Account Link for hosted onboarding
             *
             * - type: 'account_onboarding' for initial setup
             * - refresh_url: Redirect if link expires (user gets new link)
             * - return_url: Redirect after successful onboarding
             */
            $accountLink = $this->stripe->accountLinks->create([
                'account' => $partner->stripe_account_id,
                'type' => 'account_onboarding',

                // URLs for the onboarding flow
                // PLACEHOLDER: Update these to match your frontend routes
                'refresh_url' => url('/admin/partner/payouts?stripe_refresh=true'),
                'return_url' => url('/admin/partner/payouts?stripe_success=true'),

                // Collect all required information
                'collection_options' => [
                    'fields' => 'eventually_due',
                    'future_requirements' => 'include',
                ],
            ]);

            Log::info('Account link created for partner onboarding', [
                'partner_id' => $partner->id,
                'stripe_account_id' => $partner->stripe_account_id,
            ]);

            return response()->json([
                'success' => true,
                'url' => $accountLink->url,
                'expires_at' => $accountLink->expires_at,
            ]);

        } catch (ApiErrorException $e) {
            Log::error('Failed to create account link', [
                'partner_id' => $partner->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to create onboarding link: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get connected account status from Stripe API
     *
     * Always fetches fresh status from Stripe (no caching).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAccountStatus(Request $request)
    {
        $partner = $request->user()->partner;

        if (! $partner || ! $partner->stripe_account_id) {
            return response()->json([
                'connected' => false,
                'status' => null,
            ]);
        }

        if (! $this->stripe) {
            return response()->json([
                'connected' => true,
                'account_id' => $partner->stripe_account_id,
                'status' => 'unknown',
                'error' => 'Stripe not configured',
            ]);
        }

        try {
            $account = $this->stripe->accounts->retrieve($partner->stripe_account_id);

            // Determine overall status
            $status = $this->determineAccountStatus($account);

            // Update local status
            $partner->update([
                'stripe_account_status' => $status,
            ]);

            return response()->json([
                'connected' => true,
                'account_id' => $account->id,
                'status' => $status,
                'email' => $account->email,
                'country' => $account->country,

                // Key status flags
                'payouts_enabled' => $account->payouts_enabled,
                'details_submitted' => $account->details_submitted,

                // Requirements (if any)
                'requirements' => [
                    'currently_due' => $account->requirements->currently_due ?? [],
                    'eventually_due' => $account->requirements->eventually_due ?? [],
                    'past_due' => $account->requirements->past_due ?? [],
                    'disabled_reason' => $account->requirements->disabled_reason ?? null,
                ],

                // Capabilities
                'capabilities' => [
                    'transfers' => $account->capabilities->transfers ?? 'inactive',
                ],
            ]);

        } catch (ApiErrorException $e) {
            Log::error('Failed to retrieve Stripe account status', [
                'partner_id' => $partner->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'connected' => true,
                'account_id' => $partner->stripe_account_id,
                'status' => 'error',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Determine overall account status
     *
     * @param  \Stripe\Account  $account
     */
    protected function determineAccountStatus($account): string
    {
        if ($account->requirements->disabled_reason) {
            return 'disabled';
        }

        if (! empty($account->requirements->past_due)) {
            return 'restricted';
        }

        if ($account->payouts_enabled && $account->details_submitted) {
            return 'active';
        }

        return 'pending';
    }

    /**
     * Create login link for Express Dashboard
     *
     * Partners can access their Express Dashboard to view:
     * - Balance
     * - Payout history
     * - Account details
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function createDashboardLink(Request $request)
    {
        if (! $this->stripe) {
            return response()->json(['error' => 'Stripe not configured'], 500);
        }

        $partner = $request->user()->partner;

        if (! $partner || ! $partner->stripe_account_id) {
            return response()->json(['error' => 'No connected account'], 400);
        }

        try {
            $loginLink = $this->stripe->accounts->createLoginLink($partner->stripe_account_id);

            return response()->json([
                'success' => true,
                'url' => $loginLink->url,
            ]);

        } catch (ApiErrorException $e) {
            Log::error('Failed to create dashboard link', [
                'partner_id' => $partner->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to create dashboard link: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Transfer funds to a connected account
     *
     * Called by PartnerPayoutService when processing monthly payouts.
     * Transfers EUR to the partner's connected account.
     *
     * @param  int  $amountCents  Amount in EUR cents
     * @return \Stripe\Transfer
     *
     * @throws \Exception
     */
    public function transferToPartner(Partner $partner, int $amountCents, ?string $description = null)
    {
        if (! $this->stripe) {
            throw new \Exception('Stripe is not configured');
        }

        if (! $partner->stripe_account_id) {
            throw new \Exception('Partner does not have a connected Stripe account');
        }

        try {
            $transfer = $this->stripe->transfers->create([
                'amount' => $amountCents,
                'currency' => 'eur', // Cross-border payouts to MK in EUR
                'destination' => $partner->stripe_account_id,
                'description' => $description ?? 'Partner commission payout',
                'metadata' => [
                    'partner_id' => $partner->id,
                    'type' => 'commission_payout',
                ],
            ]);

            Log::info('Stripe transfer created for partner', [
                'partner_id' => $partner->id,
                'transfer_id' => $transfer->id,
                'amount_cents' => $amountCents,
            ]);

            return $transfer;

        } catch (ApiErrorException $e) {
            Log::error('Failed to create Stripe transfer', [
                'partner_id' => $partner->id,
                'amount' => $amountCents,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update bank account details
     *
     * Allows partner to update their bank account for payouts.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateBankAccount(Request $request)
    {
        $request->validate([
            'iban' => 'required|string|regex:/^MK\d{17}$/',
            'account_holder_name' => 'required|string|max:255',
        ]);

        if (! $this->stripe) {
            return response()->json(['error' => 'Stripe not configured'], 500);
        }

        $partner = $request->user()->partner;

        if (! $partner || ! $partner->stripe_account_id) {
            return response()->json(['error' => 'No connected Stripe account'], 404);
        }

        try {
            // Create new external account
            $bankAccount = $this->stripe->accounts->createExternalAccount(
                $partner->stripe_account_id,
                [
                    'external_account' => [
                        'object' => 'bank_account',
                        'country' => 'MK',
                        'currency' => 'eur',
                        'account_holder_name' => $request->account_holder_name,
                        'account_holder_type' => 'individual',
                        'account_number' => $request->iban,
                    ],
                ]
            );

            // Set as default for payouts
            $this->stripe->accounts->updateExternalAccount(
                $partner->stripe_account_id,
                $bankAccount->id,
                ['default_for_currency' => true]
            );

            Log::info('Bank account updated for partner', [
                'partner_id' => $partner->id,
                'bank_account_id' => $bankAccount->id,
            ]);

            return response()->json([
                'success' => true,
                'bank_account_id' => $bankAccount->id,
            ]);

        } catch (ApiErrorException $e) {
            Log::error('Failed to update bank account', [
                'partner_id' => $partner->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to update bank account: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete connected account (for partner offboarding)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteConnectedAccount(Request $request)
    {
        if (! $this->stripe) {
            return response()->json(['error' => 'Stripe not configured'], 500);
        }

        $partner = $request->user()->partner;

        if (! $partner || ! $partner->stripe_account_id) {
            return response()->json(['error' => 'No connected Stripe account'], 404);
        }

        try {
            $this->stripe->accounts->delete($partner->stripe_account_id);

            $partner->update([
                'stripe_account_id' => null,
                'stripe_account_status' => null,
                'payment_method' => 'bank_transfer',
            ]);

            Log::info('Stripe Connect account deleted for partner', [
                'partner_id' => $partner->id,
            ]);

            return response()->json(['success' => true]);

        } catch (ApiErrorException $e) {
            Log::error('Failed to delete Stripe account', [
                'partner_id' => $partner->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to delete Stripe account: '.$e->getMessage(),
            ], 422);
        }
    }
}
// CLAUDE-CHECKPOINT: Updated with controller properties and Account Links
