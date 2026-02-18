<?php

namespace Modules\Mk\Billing\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanySubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\Customer as StripeCustomer;
use Stripe\BillingPortal\Session as StripeBillingSession;
use Stripe\Subscription as StripeSubscription;

/**
 * Subscription Controller for Company Billing
 *
 * Handles company subscription management with Stripe
 * Supports 5 tiers: Free (€0), Starter (€12), Standard (€29), Business (€59), Max (€149)
 */
class SubscriptionController extends Controller
{
    /**
     * Subscription tier configuration
     */
    private const TIERS = [
        'free' => ['price' => 0, 'name' => 'Free'],
        'starter' => ['price' => 12, 'name' => 'Starter'],
        'standard' => ['price' => 29, 'name' => 'Standard'],
        'business' => ['price' => 59, 'name' => 'Business'],
        'max' => ['price' => 149, 'name' => 'Max'],
    ];

    /**
     * Show plan selection for company
     */
    public function index(int $companyId): JsonResponse
    {
        Log::info('SubscriptionController::index called', [
            'company_id' => $companyId,
            'user_id' => Auth::id(),
        ]);

        $company = Company::findOrFail($companyId);

        // Get current subscription tier from company (set by Stripe webhooks)
        $currentTier = $company->subscription_tier ?? 'free';

        $currentPlan = [
            'tier' => $currentTier,
            'status' => $currentTier !== 'free' ? 'active' : 'none',
            'ends_at' => null,
            'trial_ends_at' => $company->trial_ends_at,
            'paused_at' => null,
        ];

        $response = [
            'tiers' => self::TIERS,
            'current_plan' => $currentPlan,
            'stripe_customer_id' => $company->stripe_id,
        ];

        Log::info('SubscriptionController::index - Returning response', [
            'company_id' => $companyId,
            'tier' => $currentTier,
        ]);

        return response()->json($response);
    }

    /**
     * Create Stripe checkout session for company subscription
     */
    public function checkout(Request $request, int $companyId): JsonResponse
    {
        $request->validate([
            'tier' => 'required|in:starter,standard,business,max',
            'interval' => 'sometimes|in:monthly,yearly',
        ]);

        $company = Company::findOrFail($companyId);

        // Check authorization
        $this->authorize('manage-billing', $company);

        $tier = $request->input('tier');
        $interval = $request->input('interval', 'monthly');

        // Get Stripe price ID based on tier and interval
        $priceId = config("services.stripe.prices.{$tier}.{$interval}");

        if (! $priceId) {
            Log::warning('Stripe price not configured', [
                'tier' => $tier,
                'interval' => $interval,
                'config_path' => "services.stripe.prices.{$tier}.{$interval}",
            ]);

            return response()->json([
                'error' => 'Invalid tier or price not configured',
            ], 400);
        }

        try {
            // Initialize Stripe
            Stripe::setApiKey(config('services.stripe.secret'));

            // Create or retrieve Stripe customer
            $stripeCustomerId = $company->stripe_id;
            $userEmail = $company->owner->email ?? Auth::user()->email;

            if (! $stripeCustomerId) {
                $customer = StripeCustomer::create([
                    'name' => $company->name,
                    'email' => $userEmail,
                    'metadata' => [
                        'company_id' => $company->id,
                    ],
                ]);
                $stripeCustomerId = $customer->id;

                // Save customer ID to company
                $company->update(['stripe_id' => $stripeCustomerId]);
            }

            // Build success and cancel URLs
            $successUrl = url('/admin/billing/success') . '?session_id={CHECKOUT_SESSION_ID}';
            $cancelUrl = url('/admin/pricing');

            // Create Stripe Checkout Session
            $session = StripeCheckoutSession::create([
                'customer' => $stripeCustomerId,
                'payment_method_types' => ['card', 'customer_balance'],
                'payment_method_options' => [
                    'customer_balance' => [
                        'funding_type' => 'bank_transfer',
                        'bank_transfer' => [
                            'type' => 'eu_bank_transfer',
                            'eu_bank_transfer' => [
                                'country' => 'NL', // Stripe requires EU country for bank transfer
                            ],
                        ],
                    ],
                ],
                'line_items' => [[
                    'price' => $priceId,
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'subscription_data' => [
                    'trial_period_days' => 14, // 14-day free trial
                    'metadata' => [
                        'company_id' => $company->id,
                        'tier' => $tier,
                    ],
                ],
                'metadata' => [
                    'company_id' => $company->id,
                    'tier' => $tier,
                ],
                'allow_promotion_codes' => true,
            ]);

            Log::info('Stripe checkout session created', [
                'company_id' => $companyId,
                'tier' => $tier,
                'interval' => $interval,
                'session_id' => $session->id,
            ]);

            return response()->json([
                'checkout_url' => $session->url,
                'session_id' => $session->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Stripe checkout creation failed', [
                'company_id' => $companyId,
                'tier' => $tier,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to create checkout session: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Post-subscription success page
     */
    public function success(int $companyId): JsonResponse
    {
        $company = Company::findOrFail($companyId);

        // Reload subscription data from CompanySubscription
        $subscription = CompanySubscription::where('company_id', $company->id)
            ->whereIn('status', ['trial', 'active'])
            ->latest()
            ->first();

        return response()->json([
            'message' => 'Subscription activated successfully!',
            'subscription' => [
                'tier' => $company->subscription_tier,
                'status' => $subscription?->status,
                'trial_ends_at' => $subscription?->trial_ends_at,
            ],
        ]);
    }

    /**
     * Subscription management dashboard - opens Stripe Customer Portal
     */
    public function manage(int $companyId): JsonResponse
    {
        $company = Company::findOrFail($companyId);

        // Check authorization
        $this->authorize('manage-billing', $company);

        if (! $company->stripe_id) {
            return response()->json([
                'error' => 'No billing account found',
            ], 404);
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            // Create Stripe Billing Portal session
            $session = StripeBillingSession::create([
                'customer' => $company->stripe_id,
                'return_url' => url('/admin/billing'),
            ]);

            return response()->json([
                'portal_url' => $session->url,
                'subscription' => [
                    'tier' => $company->subscription_tier ?? 'free',
                    'stripe_id' => $company->stripe_id,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Stripe portal session failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to open billing portal',
            ], 500);
        }
    }

    /**
     * Change subscription plan (upgrade/downgrade)
     */
    public function swap(Request $request, int $companyId): JsonResponse
    {
        $request->validate([
            'tier' => 'required|in:starter,standard,business,max',
        ]);

        $company = Company::findOrFail($companyId);

        // Check authorization
        $this->authorize('manage-billing', $company);

        // Find active Stripe subscription
        $companySub = CompanySubscription::where('company_id', $company->id)
            ->where('provider', 'stripe')
            ->whereIn('status', ['trial', 'active'])
            ->first();

        if (! $companySub || ! $companySub->provider_subscription_id) {
            return response()->json([
                'error' => 'No active Stripe subscription to modify',
            ], 400);
        }

        $newTier = $request->input('tier');
        $newPriceId = config("services.stripe.prices.{$newTier}.monthly");

        if (! $newPriceId) {
            return response()->json([
                'error' => 'Invalid tier or price not configured',
            ], 400);
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            // Retrieve Stripe subscription and swap the price
            $stripeSub = StripeSubscription::retrieve($companySub->provider_subscription_id);
            StripeSubscription::update($stripeSub->id, [
                'items' => [[
                    'id' => $stripeSub->items->data[0]->id,
                    'price' => $newPriceId,
                ]],
                'proration_behavior' => 'create_prorations',
                'metadata' => [
                    'company_id' => $company->id,
                    'tier' => $newTier,
                ],
            ]);

            // Update local records
            $companySub->update([
                'plan' => $newTier,
                'price_monthly' => self::TIERS[$newTier]['price'] ?? 0,
            ]);
            $company->update(['subscription_tier' => $newTier]);

            Log::info('Subscription plan swapped', [
                'company_id' => $companyId,
                'new_tier' => $newTier,
            ]);

            return response()->json([
                'message' => 'Subscription plan updated successfully',
                'tier' => $newTier,
            ]);

        } catch (\Exception $e) {
            Log::error('Subscription swap failed', [
                'company_id' => $companyId,
                'new_tier' => $newTier,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to update subscription plan',
            ], 500);
        }
    }

    /**
     * Cancel subscription
     */
    public function cancel(Request $request, int $companyId): JsonResponse
    {
        $company = Company::findOrFail($companyId);

        // Check authorization
        $this->authorize('manage-billing', $company);

        $companySub = CompanySubscription::where('company_id', $company->id)
            ->where('provider', 'stripe')
            ->whereIn('status', ['trial', 'active'])
            ->first();

        if (! $companySub || ! $companySub->provider_subscription_id) {
            return response()->json([
                'error' => 'No active subscription to cancel',
            ], 400);
        }

        try {
            // Cancel on Stripe (at period end so access continues)
            Stripe::setApiKey(config('services.stripe.secret'));
            StripeSubscription::update($companySub->provider_subscription_id, [
                'cancel_at_period_end' => true,
            ]);

            Log::info('Subscription cancelled', [
                'company_id' => $companyId,
                'tier' => $company->subscription_tier,
            ]);

            return response()->json([
                'message' => 'Subscription cancelled. Access will continue until the end of the billing period.',
            ]);

        } catch (\Exception $e) {
            Log::error('Subscription cancellation failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to cancel subscription',
            ], 500);
        }
    }

    /**
     * Resume a cancelled subscription
     */
    public function resume(int $companyId): JsonResponse
    {
        $company = Company::findOrFail($companyId);

        // Check authorization
        $this->authorize('manage-billing', $company);

        $companySub = CompanySubscription::where('company_id', $company->id)
            ->where('provider', 'stripe')
            ->first();

        if (! $companySub || ! $companySub->provider_subscription_id) {
            return response()->json([
                'error' => 'No subscription to resume',
            ], 400);
        }

        try {
            // Resume on Stripe (remove cancel_at_period_end)
            Stripe::setApiKey(config('services.stripe.secret'));
            StripeSubscription::update($companySub->provider_subscription_id, [
                'cancel_at_period_end' => false,
            ]);

            // Reactivate locally
            $companySub->activate();

            Log::info('Subscription resumed', [
                'company_id' => $companyId,
            ]);

            return response()->json([
                'message' => 'Subscription resumed successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Subscription resume failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to resume subscription',
            ], 500);
        }
    }
} // CLAUDE-CHECKPOINT
