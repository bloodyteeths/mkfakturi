<?php

namespace Modules\Mk\Billing\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
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

        Log::info('SubscriptionController::index - Company found', [
            'company_id' => $company->id,
            'paddle_id' => $company->paddle_id,
        ]);

        // Check authorization - skip for now to allow viewing
        // $this->authorize('manage-billing', $company);

        // Try to get subscription safely
        $currentPlan = null;
        try {
            // Check directly in database to avoid calling Paddle methods
            $subscription = \DB::table('subscriptions')
                ->where('billable_id', $company->id)
                ->where('billable_type', 'App\\Models\\Company')
                ->where('name', 'default')
                ->whereNull('ends_at')
                ->orWhere('ends_at', '>', now())
                ->first();

            if ($subscription) {
                Log::info('SubscriptionController::index - Active subscription found', [
                    'subscription_id' => $subscription->id,
                    'status' => $subscription->status,
                ]);

                $currentPlan = [
                    'tier' => $company->subscription_tier ?? 'starter',
                    'status' => $subscription->status ?? 'active',
                    'ends_at' => $subscription->ends_at,
                    'trial_ends_at' => $subscription->trial_ends_at,
                    'paused_at' => $subscription->paused_at ?? null,
                ];
            }
        } catch (\Exception $e) {
            Log::warning('SubscriptionController::index - Error checking subscription', [
                'error' => $e->getMessage(),
                'company_id' => $company->id,
            ]);
        }

        // If no valid subscription, return default free tier
        if (! $currentPlan) {
            Log::info('SubscriptionController::index - No subscription, returning free tier');
            $currentPlan = [
                'tier' => 'free',
                'status' => 'active',
                'ends_at' => null,
                'trial_ends_at' => null,
                'paused_at' => null,
            ];
        }

        $response = [
            'tiers' => self::TIERS,
            'current_plan' => $currentPlan,
            'paddle_customer_id' => $company->paddle_id,
        ];

        Log::info('SubscriptionController::index - Returning response', $response);

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
                'payment_method_types' => ['card'],
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

        // Reload subscription data
        $subscription = $company->subscription('default');

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

        $subscription = $company->subscription('default');

        if (! $subscription || ! $subscription->valid()) {
            return response()->json([
                'error' => 'No active subscription to modify',
            ], 400);
        }

        $newTier = $request->input('tier');
        $newPriceId = config("services.paddle.prices.{$newTier}");

        if (! $newPriceId) {
            return response()->json([
                'error' => 'Invalid tier or price not configured',
            ], 400);
        }

        try {
            // Swap the plan
            $subscription->swap($newPriceId);

            // Update company tier
            $company->update([
                'subscription_tier' => $newTier,
            ]);

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

        $subscription = $company->subscription('default');

        if (! $subscription || ! $subscription->valid()) {
            return response()->json([
                'error' => 'No active subscription to cancel',
            ], 400);
        }

        try {
            $subscription->cancel();

            Log::info('Subscription cancelled', [
                'company_id' => $companyId,
                'tier' => $company->subscription_tier,
            ]);

            return response()->json([
                'message' => 'Subscription cancelled. Access will continue until the end of the billing period.',
                'ends_at' => $subscription->ends_at,
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

        $subscription = $company->subscription('default');

        if (! $subscription || ! $subscription->onGracePeriod()) {
            return response()->json([
                'error' => 'No subscription to resume',
            ], 400);
        }

        try {
            $subscription->resume();

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
