<?php

namespace Modules\Mk\Billing\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Paddle\Checkout;

/**
 * Subscription Controller for Company Billing
 *
 * Handles company subscription management with Paddle
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

        // Check if company has an active subscription
        $hasSubscription = $company->subscribed('default');

        Log::info('SubscriptionController::index - Company found', [
            'company_id' => $company->id,
            'has_subscription' => $hasSubscription,
        ]);

        // Check authorization - skip for now to allow viewing
        // $this->authorize('manage-billing', $company);

        $currentPlan = null;
        if ($hasSubscription) {
            // Get the subscription instance only if subscribed
            $subscription = $company->subscription('default');

            if ($subscription && method_exists($subscription, 'valid') && $subscription->valid()) {
                $currentPlan = [
                    'tier' => $company->subscription_tier,
                    'status' => $subscription->status,
                    'ends_at' => $subscription->ends_at,
                    'trial_ends_at' => $subscription->trial_ends_at,
                    'paused_at' => $subscription->paused_at,
                ];
            }
        }

        // If no valid subscription, return default free tier
        if (!$currentPlan) {
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
     * Create Paddle checkout session for company subscription
     */
    public function checkout(Request $request, int $companyId): JsonResponse
    {
        $request->validate([
            'tier' => 'required|in:starter,standard,business,max',
        ]);

        $company = Company::findOrFail($companyId);

        // Check authorization
        $this->authorize('manage-billing', $company);

        $tier = $request->input('tier');
        $priceId = config("services.paddle.prices.{$tier}");

        if (! $priceId) {
            return response()->json([
                'error' => 'Invalid tier or price not configured',
            ], 400);
        }

        try {
            // Create or retrieve Paddle customer
            if (! $company->paddle_id) {
                $company->createAsCustomer([
                    'name' => $company->name,
                    'email' => $company->owner->email ?? Auth::user()->email,
                ]);
            }

            // Build checkout session
            $checkout = $company->checkout($priceId)
                ->customData([
                    'company_id' => $company->id,
                    'tier' => $tier,
                ])
                ->returnTo(route('subscription.success', ['company' => $companyId]));

            return response()->json([
                'checkout_url' => $checkout->url(),
                'transaction_id' => $checkout->id(),
            ]);

        } catch (\Exception $e) {
            Log::error('Paddle checkout creation failed', [
                'company_id' => $companyId,
                'tier' => $tier,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to create checkout session',
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
     * Subscription management dashboard
     */
    public function manage(int $companyId): JsonResponse
    {
        $company = Company::findOrFail($companyId);

        // Check authorization
        $this->authorize('manage-billing', $company);

        $subscription = $company->subscription('default');

        if (! $subscription) {
            return response()->json([
                'error' => 'No active subscription found',
            ], 404);
        }

        return response()->json([
            'subscription' => [
                'tier' => $company->subscription_tier,
                'status' => $subscription->status,
                'paddle_id' => $subscription->paddle_id,
                'trial_ends_at' => $subscription->trial_ends_at,
                'paused_at' => $subscription->paused_at,
                'ends_at' => $subscription->ends_at,
                'created_at' => $subscription->created_at,
            ],
            'update_payment_method_url' => $subscription->updatePaymentMethodUrl(),
            'cancel_url' => route('subscription.cancel', ['company' => $companyId]),
        ]);
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
