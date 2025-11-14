<?php

namespace Modules\Mk\Partner\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Partner Plus Subscription Controller
 *
 * Handles Partner Plus subscription (€29/mo) for accountants
 * Provides enhanced commission rate (22% vs 18%)
 */
class PartnerSubscriptionController extends Controller
{
    /**
     * Partner Plus price: €29/month
     */
    private const PARTNER_PLUS_PRICE = 29;

    /**
     * Show partner subscription status
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();

        // Check if user is a partner
        if (!$user->partner_tier || $user->partner_tier === 'none') {
            return response()->json([
                'error' => 'User is not a registered partner'
            ], 403);
        }

        // Get current subscription
        $subscription = $user->subscription('partner_plus');

        $currentPlan = [
            'tier' => $user->partner_subscription_tier ?? 'free',
            'price' => $user->partner_subscription_tier === 'plus' ? self::PARTNER_PLUS_PRICE : 0,
            'commission_rate' => $user->partner_subscription_tier === 'plus' ? 0.22 : 0.18,
        ];

        if ($subscription && $subscription->valid()) {
            $currentPlan['status'] = $subscription->status;
            $currentPlan['ends_at'] = $subscription->ends_at;
            $currentPlan['trial_ends_at'] = $subscription->trial_ends_at;
        }

        return response()->json([
            'current_plan' => $currentPlan,
            'paddle_customer_id' => $user->paddle_id,
        ]);
    }

    /**
     * Create Paddle checkout session for Partner Plus
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkout(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Check if user is a partner
        if (!$user->partner_tier || $user->partner_tier === 'none') {
            return response()->json([
                'error' => 'User is not a registered partner'
            ], 403);
        }

        // Check if already subscribed
        if ($user->subscription('partner_plus')?->valid()) {
            return response()->json([
                'error' => 'Already subscribed to Partner Plus'
            ], 400);
        }

        $priceId = config('services.paddle.prices.partner_plus');

        if (!$priceId) {
            return response()->json([
                'error' => 'Partner Plus price not configured'
            ], 400);
        }

        try {
            // Create or retrieve Paddle customer
            if (!$user->paddle_id) {
                $user->createAsCustomer([
                    'name' => $user->name,
                    'email' => $user->email,
                ]);
            }

            // Build checkout session
            $checkout = $user->checkout($priceId)
                ->customData([
                    'user_id' => $user->id,
                    'subscription_type' => 'partner_plus',
                ])
                ->returnTo(route('partner.subscription.success'));

            return response()->json([
                'checkout_url' => $checkout->url(),
                'transaction_id' => $checkout->id(),
            ]);

        } catch (\Exception $e) {
            Log::error('Partner Plus checkout creation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to create checkout session'
            ], 500);
        }
    }

    /**
     * Post-subscription success page
     *
     * @return JsonResponse
     */
    public function success(): JsonResponse
    {
        $user = Auth::user();

        // Reload subscription data
        $subscription = $user->subscription('partner_plus');

        return response()->json([
            'message' => 'Partner Plus subscription activated! You now earn 22% commission.',
            'subscription' => [
                'tier' => 'plus',
                'status' => $subscription?->status,
                'commission_rate' => 0.22,
                'trial_ends_at' => $subscription?->trial_ends_at,
            ],
        ]);
    }

    /**
     * Subscription management dashboard
     *
     * @return JsonResponse
     */
    public function manage(): JsonResponse
    {
        $user = Auth::user();

        $subscription = $user->subscription('partner_plus');

        if (!$subscription) {
            return response()->json([
                'error' => 'No active Partner Plus subscription found'
            ], 404);
        }

        return response()->json([
            'subscription' => [
                'tier' => 'plus',
                'status' => $subscription->status,
                'paddle_id' => $subscription->paddle_id,
                'trial_ends_at' => $subscription->trial_ends_at,
                'paused_at' => $subscription->paused_at,
                'ends_at' => $subscription->ends_at,
                'created_at' => $subscription->created_at,
                'commission_rate' => 0.22,
            ],
            'update_payment_method_url' => $subscription->updatePaymentMethodUrl(),
            'cancel_url' => route('partner.subscription.cancel'),
        ]);
    }

    /**
     * Cancel Partner Plus subscription
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function cancel(Request $request): JsonResponse
    {
        $user = Auth::user();

        $subscription = $user->subscription('partner_plus');

        if (!$subscription || !$subscription->valid()) {
            return response()->json([
                'error' => 'No active subscription to cancel'
            ], 400);
        }

        try {
            $subscription->cancel();

            Log::info('Partner Plus subscription cancelled', [
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Partner Plus subscription cancelled. You will revert to 18% commission rate at the end of the billing period.',
                'ends_at' => $subscription->ends_at,
            ]);

        } catch (\Exception $e) {
            Log::error('Partner Plus cancellation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to cancel subscription'
            ], 500);
        }
    }

    /**
     * Resume a cancelled subscription
     *
     * @return JsonResponse
     */
    public function resume(): JsonResponse
    {
        $user = Auth::user();

        $subscription = $user->subscription('partner_plus');

        if (!$subscription || !$subscription->onGracePeriod()) {
            return response()->json([
                'error' => 'No subscription to resume'
            ], 400);
        }

        try {
            $subscription->resume();

            Log::info('Partner Plus subscription resumed', [
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Partner Plus subscription resumed successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Partner Plus resume failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to resume subscription'
            ], 500);
        }
    }
} // CLAUDE-CHECKPOINT
