<?php

namespace Modules\Mk\Partner\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Partner\Services\PartnerUsageLimitService;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\Customer as StripeCustomer;
use Stripe\BillingPortal\Session as StripeBillingSession;
use Stripe\Subscription as StripeSubscription;

/**
 * Partner Subscription Controller
 *
 * Handles accountant subscription management with Stripe.
 * 4 tiers: Start (€29), Office (€59), Pro (€99), Elite (€199)
 * + Seat add-on (€5/seat/month)
 * Yearly billing: 2 months free (pay for 10)
 */
class PartnerSubscriptionController extends Controller
{
    /**
     * Show partner subscription status + usage meters
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        $partner = $this->getPartner();

        if (!$partner) {
            return response()->json(['error' => 'User is not a registered partner'], 403);
        }

        $usageService = app(PartnerUsageLimitService::class);
        $tier = $usageService->getPartnerTier($partner);
        $tierConfig = config("subscriptions.partner_tiers.{$tier}", []);

        $currentPlan = [
            'tier' => $tier,
            'name' => $tierConfig['name'] ?? ucfirst($tier),
            'price_eur' => $tierConfig['price_monthly_eur'] ?? 0,
            'price_mkd' => $tierConfig['price_monthly_mkd'] ?? 0,
            'status' => $this->getSubscriptionStatus($user),
            'seats' => $user->partner_seat_count ?? 0,
            'seat_price_eur' => config('subscriptions.partner_seat_price.eur', 5),
        ];

        // Trial info
        $trial = [
            'is_trial' => $usageService->isOnTrial($partner),
            'days_remaining' => $usageService->getTrialDaysRemaining($partner),
            'trial_ends_at' => $user->partner_trial_ends_at,
            'is_expired' => $usageService->isTrialExpired($partner),
            'is_hard_blocked' => $usageService->isHardBlocked($partner),
        ];

        // Usage meters
        $usage = $usageService->getAllUsage($partner);

        // Available tiers for comparison
        $tiers = [];
        foreach (config('subscriptions.partner_tiers', []) as $tierKey => $tierConf) {
            if ($tierKey === 'free') continue;
            $tiers[$tierKey] = [
                'name' => $tierConf['name'],
                'price_eur' => $tierConf['price_monthly_eur'],
                'price_mkd' => $tierConf['price_monthly_mkd'],
                'price_yearly_eur' => $tierConf['price_yearly_eur'] ?? $tierConf['price_monthly_eur'] * 10,
                'price_yearly_mkd' => $tierConf['price_yearly_mkd'] ?? $tierConf['price_monthly_mkd'] * 10,
                'limits' => $tierConf['limits'],
                'support_response_hours' => $tierConf['support_response_hours'] ?? null,
            ];
        }

        return response()->json([
            'current_plan' => $currentPlan,
            'trial' => $trial,
            'usage' => $usage,
            'tiers' => $tiers,
            'stripe_customer_id' => $user->stripe_customer_id,
        ]);
    }

    /**
     * Create Stripe checkout session for partner subscription
     */
    public function checkout(Request $request): JsonResponse
    {
        $request->validate([
            'tier' => 'required|in:start,office,pro,elite',
            'seats' => 'sometimes|integer|min:0|max:50',
            'currency' => 'sometimes|in:mkd,eur',
            'billing_cycle' => 'sometimes|in:monthly,yearly',
        ]);

        $user = Auth::user();
        $partner = $this->getPartner();

        if (!$partner) {
            return response()->json(['error' => 'User is not a registered partner'], 403);
        }

        $tier = $request->input('tier');
        $seats = $request->input('seats', 0);
        $currency = strtolower($request->input('currency', 'mkd'));
        $billingCycle = $request->input('billing_cycle', 'monthly');

        // Get Stripe price ID based on currency + billing cycle
        if ($billingCycle === 'yearly') {
            $pricesKey = $currency === 'eur' ? 'services.stripe.partner_prices_eur_yearly' : 'services.stripe.partner_prices_yearly';
        } else {
            $pricesKey = $currency === 'eur' ? 'services.stripe.partner_prices_eur' : 'services.stripe.partner_prices';
        }
        $priceId = config("{$pricesKey}.{$tier}");

        if (!$priceId) {
            return response()->json(['error' => 'Invalid tier or price not configured'], 400);
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            // Create or retrieve Stripe customer
            $stripeCustomerId = $user->stripe_customer_id;
            if (!$stripeCustomerId) {
                $customer = StripeCustomer::create([
                    'name' => $partner->name ?: $user->name,
                    'email' => $user->email,
                    'metadata' => [
                        'user_id' => $user->id,
                        'partner_id' => $partner->id,
                        'type' => 'partner',
                    ],
                ]);
                $stripeCustomerId = $customer->id;
                $user->update(['stripe_customer_id' => $stripeCustomerId]);
            }

            // Build line items
            $lineItems = [
                ['price' => $priceId, 'quantity' => 1],
            ];

            // Add seat line item if seats requested
            if ($seats > 0) {
                $seatPriceId = config("{$pricesKey}.seat");
                if ($seatPriceId) {
                    $lineItems[] = ['price' => $seatPriceId, 'quantity' => $seats];
                }
            }

            // Payment methods: card for MKD, card + SEPA for EUR
            $paymentMethods = $currency === 'eur'
                ? ['card', 'sepa_debit']
                : ['card'];

            $successUrl = url('/partner/billing') . '?success=1&session_id={CHECKOUT_SESSION_ID}';
            $cancelUrl = url('/partner/billing');

            $session = StripeCheckoutSession::create([
                'customer' => $stripeCustomerId,
                'payment_method_types' => $paymentMethods,
                'line_items' => $lineItems,
                'mode' => 'subscription',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'subscription_data' => [
                    'metadata' => [
                        'type' => 'partner_subscription',
                        'user_id' => $user->id,
                        'partner_id' => $partner->id,
                        'tier' => $tier,
                        'seats' => $seats,
                        'billing_cycle' => $billingCycle,
                    ],
                ],
                'metadata' => [
                    'type' => 'partner_subscription',
                    'user_id' => $user->id,
                    'partner_id' => $partner->id,
                    'tier' => $tier,
                    'billing_cycle' => $billingCycle,
                    'payment_currency' => $currency,
                ],
                'allow_promotion_codes' => true,
            ]);

            Log::info('Partner Stripe checkout session created', [
                'user_id' => $user->id,
                'partner_id' => $partner->id,
                'tier' => $tier,
                'seats' => $seats,
                'billing_cycle' => $billingCycle,
                'session_id' => $session->id,
            ]);

            return response()->json([
                'checkout_url' => $session->url,
                'session_id' => $session->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Partner Stripe checkout failed', [
                'user_id' => $user->id,
                'tier' => $tier,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Failed to create checkout session'], 500);
        }
    }

    /**
     * Stripe Billing Portal for subscription management
     */
    public function manage(): JsonResponse
    {
        $user = Auth::user();

        if (!$user->stripe_customer_id) {
            return response()->json(['error' => 'No billing account found'], 404);
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            $session = StripeBillingSession::create([
                'customer' => $user->stripe_customer_id,
                'return_url' => url('/partner/billing'),
            ]);

            return response()->json([
                'portal_url' => $session->url,
            ]);

        } catch (\Exception $e) {
            Log::error('Partner Stripe portal failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Failed to open billing portal'], 500);
        }
    }

    /**
     * Change subscription plan (upgrade/downgrade)
     */
    public function swap(Request $request): JsonResponse
    {
        $request->validate([
            'tier' => 'required|in:start,office,pro,elite',
        ]);

        $user = Auth::user();

        if (!$user->stripe_subscription_id) {
            return response()->json(['error' => 'No active subscription to modify'], 400);
        }

        $newTier = $request->input('tier');

        // Detect current billing cycle from Stripe subscription to use matching price
        $isYearly = false;
        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            $currentSub = StripeSubscription::retrieve($user->stripe_subscription_id);
            if ($currentSub->items->data[0]->price->recurring->interval === 'year') {
                $isYearly = true;
            }
        } catch (\Exception $e) {
            // Fall back to monthly
        }

        $pricesKey = $isYearly ? 'services.stripe.partner_prices_yearly' : 'services.stripe.partner_prices';
        $newPriceId = config("{$pricesKey}.{$newTier}");

        if (!$newPriceId) {
            return response()->json(['error' => 'Invalid tier or price not configured'], 400);
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            $stripeSub = StripeSubscription::retrieve($user->stripe_subscription_id);

            // Find the tier line item (not the seat add-on)
            $tierItem = null;
            $seatPriceIds = array_filter([
                config('services.stripe.partner_prices.seat'),
                config('services.stripe.partner_prices_eur.seat'),
                config('services.stripe.partner_prices_yearly.seat'),
                config('services.stripe.partner_prices_eur_yearly.seat'),
            ]);

            foreach ($stripeSub->items->data as $item) {
                if (!in_array($item->price->id, $seatPriceIds)) {
                    $tierItem = $item;
                    break;
                }
            }

            if (!$tierItem) {
                return response()->json(['error' => 'Could not find tier subscription item'], 400);
            }

            StripeSubscription::update($stripeSub->id, [
                'items' => [[
                    'id' => $tierItem->id,
                    'price' => $newPriceId,
                ]],
                'proration_behavior' => 'create_prorations',
                'metadata' => [
                    'type' => 'partner_subscription',
                    'tier' => $newTier,
                    'user_id' => $user->id,
                ],
            ]);

            $user->update(['partner_subscription_tier' => $newTier]);

            Log::info('Partner subscription plan swapped', [
                'user_id' => $user->id,
                'new_tier' => $newTier,
            ]);

            return response()->json([
                'message' => 'Subscription plan updated successfully',
                'tier' => $newTier,
            ]);

        } catch (\Exception $e) {
            Log::error('Partner subscription swap failed', [
                'user_id' => $user->id,
                'new_tier' => $newTier,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Failed to update subscription plan'], 500);
        }
    }

    /**
     * Cancel subscription at period end
     */
    public function cancel(): JsonResponse
    {
        $user = Auth::user();

        if (!$user->stripe_subscription_id) {
            return response()->json(['error' => 'No active subscription to cancel'], 400);
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            StripeSubscription::update($user->stripe_subscription_id, [
                'cancel_at_period_end' => true,
            ]);

            Log::info('Partner subscription cancelled', [
                'user_id' => $user->id,
                'tier' => $user->partner_subscription_tier,
            ]);

            return response()->json([
                'message' => 'Subscription cancelled. Access will continue until the end of the billing period.',
            ]);

        } catch (\Exception $e) {
            Log::error('Partner subscription cancellation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Failed to cancel subscription'], 500);
        }
    }

    /**
     * Resume a cancelled subscription
     */
    public function resume(): JsonResponse
    {
        $user = Auth::user();

        if (!$user->stripe_subscription_id) {
            return response()->json(['error' => 'No subscription to resume'], 400);
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            StripeSubscription::update($user->stripe_subscription_id, [
                'cancel_at_period_end' => false,
            ]);

            Log::info('Partner subscription resumed', [
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Subscription resumed successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Partner subscription resume failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Failed to resume subscription'], 500);
        }
    }

    /**
     * Get subscription status string
     */
    protected function getSubscriptionStatus($user): string
    {
        if ($user->stripe_subscription_id) {
            return 'active';
        }

        if ($user->partner_trial_ends_at && now()->lt($user->partner_trial_ends_at)) {
            return 'trial';
        }

        if ($user->partner_trial_ends_at && now()->gte($user->partner_trial_ends_at)) {
            return 'expired';
        }

        return 'none';
    }

    /**
     * Get partner from authenticated user
     */
    protected function getPartner(): ?Partner
    {
        $user = Auth::user();
        if (!$user) return null;

        return Partner::where('user_id', $user->id)->first();
    }
}
// CLAUDE-CHECKPOINT
