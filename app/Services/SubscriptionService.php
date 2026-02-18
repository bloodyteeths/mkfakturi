<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Services\CpayDriver;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\Customer as StripeCustomer;
use Stripe\Subscription as StripeSubscription;

/**
 * Unified Subscription Service
 *
 * Provides an abstraction layer for subscription management across
 * multiple payment providers (Paddle and CPAY)
 */
class SubscriptionService
{
    protected CpayDriver $cpayDriver;

    public function __construct(CpayDriver $cpayDriver)
    {
        $this->cpayDriver = $cpayDriver;
    }

    /**
     * Subscription tier pricing (in EUR)
     */
    private const TIER_PRICING = [
        'free' => 0,
        'starter' => 12,
        'standard' => 29,
        'business' => 59,
        'max' => 149,
        'partner_plus' => 29,
    ];

    /**
     * Create a company subscription
     *
     * @param  string  $tier  Subscription tier (starter, standard, business, max)
     * @param  string  $provider  Payment provider (paddle or cpay)
     * @return array Contains 'checkout_url' and provider-specific data
     *
     * @throws \Exception
     */
    public function createCompanySubscription(Company $company, string $tier, string $provider = 'paddle'): array
    {
        // Validate tier
        if (! in_array($tier, ['starter', 'standard', 'business', 'max'])) {
            throw new \InvalidArgumentException("Invalid subscription tier: {$tier}");
        }

        // Validate provider
        if (! in_array($provider, ['paddle', 'cpay', 'stripe'])) {
            throw new \InvalidArgumentException("Invalid payment provider: {$provider}");
        }

        $monthlyPrice = self::TIER_PRICING[$tier];

        Log::info('Creating company subscription', [
            'company_id' => $company->id,
            'tier' => $tier,
            'provider' => $provider,
            'price' => $monthlyPrice,
        ]);

        if ($provider === 'paddle') {
            return $this->createPaddleSubscription($company, $tier, $monthlyPrice);
        } elseif ($provider === 'stripe') {
            return $this->createStripeSubscription($company, $tier, $monthlyPrice);
        } else {
            return $this->createCpaySubscription($company, $tier, $monthlyPrice);
        }
    }

    /**
     * Create Partner Plus subscription for user
     *
     * @param  string  $provider  Payment provider (paddle or cpay)
     * @return array Contains 'checkout_url' and provider-specific data
     *
     * @throws \Exception
     */
    public function createPartnerPlusSubscription(User $user, string $provider = 'paddle'): array
    {
        // Verify user is a partner
        if (! $user->partner_tier || $user->partner_tier === 'none') {
            throw new \Exception('User is not a registered partner');
        }

        $monthlyPrice = self::TIER_PRICING['partner_plus'];

        Log::info('Creating Partner Plus subscription', [
            'user_id' => $user->id,
            'provider' => $provider,
            'price' => $monthlyPrice,
        ]);

        if ($provider === 'paddle') {
            return $this->createPaddlePartnerSubscription($user, $monthlyPrice);
        } else {
            throw new \Exception('CPAY is not yet supported for Partner Plus subscriptions');
        }
    }

    /**
     * Swap subscription plan (upgrade/downgrade)
     *
     * @param  \Laravel\Paddle\Subscription  $subscription
     * @return bool Success status
     *
     * @throws \Exception
     */
    public function swapPlan($subscription, string $newTier): bool
    {
        if (! $subscription) {
            throw new \Exception('No active subscription found');
        }

        // Check provider
        $provider = $subscription->provider ?? 'paddle';

        if ($provider === 'paddle') {
            return $this->swapPaddlePlan($subscription, $newTier);
        } elseif ($provider === 'stripe') {
            return $this->swapStripePlan($subscription, $newTier);
        } elseif ($provider === 'cpay') {
            return $this->swapCpayPlan($subscription, $newTier);
        }

        throw new \Exception("Unsupported provider: {$provider}");
    }

    /**
     * Cancel subscription
     *
     * @param  \Laravel\Paddle\Subscription  $subscription
     * @return bool Success status
     *
     * @throws \Exception
     */
    public function cancelSubscription($subscription): bool
    {
        if (! $subscription) {
            throw new \Exception('No active subscription found');
        }

        // Check provider
        $provider = $subscription->provider ?? 'paddle';

        if ($provider === 'paddle') {
            $subscription->cancel();

            Log::info('Paddle subscription cancelled', [
                'subscription_id' => $subscription->id,
            ]);

            return true;

        } elseif ($provider === 'stripe') {
            return $this->cancelStripeSubscription($subscription);

        } elseif ($provider === 'cpay') {
            // Extract CPAY subscription reference from metadata
            $metadata = is_string($subscription->metadata)
                ? json_decode($subscription->metadata, true)
                : $subscription->metadata;

            $subscriptionRef = $metadata['cpay_subscription_ref'] ?? null;

            if (! $subscriptionRef) {
                throw new \Exception('CPAY subscription reference not found');
            }

            return $this->cpayDriver->cancelSubscription($subscriptionRef);
        }

        throw new \Exception("Unsupported provider: {$provider}");
    }

    /**
     * Create Paddle subscription for company
     */
    private function createPaddleSubscription(Company $company, string $tier, float $monthlyPrice): array
    {
        $priceId = config("services.paddle.prices.{$tier}");

        if (! $priceId) {
            throw new \Exception("Paddle price ID not configured for tier: {$tier}");
        }

        // Create or retrieve Paddle customer
        if (! $company->paddle_id) {
            $company->createAsCustomer([
                'name' => $company->name,
                'email' => $company->owner->email ?? auth()->user()->email,
            ]);
        }

        // Build checkout session
        $checkout = $company->checkout($priceId)
            ->customData([
                'company_id' => $company->id,
                'tier' => $tier,
            ])
            ->returnTo(route('subscription.success', ['company' => $company->id]));

        return [
            'provider' => 'paddle',
            'checkout_url' => $checkout->url(),
            'transaction_id' => $checkout->id(),
        ];
    }

    /**
     * Create CPAY subscription for company
     */
    private function createCpaySubscription(Company $company, string $tier, float $monthlyPrice): array
    {
        $result = $this->cpayDriver->createSubscription($company, $tier, $monthlyPrice);

        return [
            'provider' => 'cpay',
            'checkout_url' => $result['checkout_url'],
            'subscription_ref' => $result['subscription_ref'],
        ];
    }

    /**
     * Create Paddle Partner Plus subscription
     */
    private function createPaddlePartnerSubscription(User $user, float $monthlyPrice): array
    {
        $priceId = config('services.paddle.prices.partner_plus');

        if (! $priceId) {
            throw new \Exception('Paddle price ID not configured for Partner Plus');
        }

        // Create or retrieve Paddle customer
        if (! $user->paddle_id) {
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

        return [
            'provider' => 'paddle',
            'checkout_url' => $checkout->url(),
            'transaction_id' => $checkout->id(),
        ];
    }

    /**
     * Swap Paddle subscription plan
     *
     * @param  \Laravel\Paddle\Subscription  $subscription
     */
    private function swapPaddlePlan($subscription, string $newTier): bool
    {
        $newPriceId = config("services.paddle.prices.{$newTier}");

        if (! $newPriceId) {
            throw new \Exception("Paddle price ID not configured for tier: {$newTier}");
        }

        $subscription->swap($newPriceId);

        // Update company tier
        if ($subscription->billable instanceof Company) {
            $subscription->billable->update([
                'subscription_tier' => $newTier,
            ]);
        }

        Log::info('Paddle subscription plan swapped', [
            'subscription_id' => $subscription->id,
            'new_tier' => $newTier,
        ]);

        return true;
    }

    /**
     * Swap CPAY subscription plan
     *
     * @param  \Laravel\Paddle\Subscription  $subscription
     */
    private function swapCpayPlan($subscription, string $newTier): bool
    {
        // For CPAY, we need to cancel the old subscription and create a new one
        // This is a simplified approach - actual implementation may vary
        throw new \Exception('CPAY plan swapping not yet implemented. Please cancel and create a new subscription.');
    }

    /**
     * Create Stripe subscription checkout session for company
     */
    private function createStripeSubscription(Company $company, string $tier, float $monthlyPrice): array
    {
        $interval = 'monthly';
        $priceId = config("services.stripe.prices.{$tier}.{$interval}");

        if (! $priceId) {
            throw new \Exception("Stripe price ID not configured for tier: {$tier}");
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        // Create or retrieve Stripe customer
        if (! $company->stripe_id) {
            $customer = StripeCustomer::create([
                'name' => $company->name,
                'email' => $company->owner->email ?? auth()->user()->email,
                'metadata' => ['company_id' => $company->id],
            ]);
            $company->update(['stripe_id' => $customer->id]);
        }

        $successUrl = url('/admin/billing/success').'?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = url('/admin/pricing');

        $session = StripeCheckoutSession::create([
            'customer' => $company->stripe_id,
            'payment_method_types' => ['card', 'customer_balance'],
            'payment_method_options' => [
                'customer_balance' => [
                    'funding_type' => 'bank_transfer',
                    'bank_transfer' => [
                        'type' => 'eu_bank_transfer',
                        'eu_bank_transfer' => ['country' => 'NL'],
                    ],
                ],
            ],
            'line_items' => [['price' => $priceId, 'quantity' => 1]],
            'mode' => 'subscription',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'subscription_data' => [
                'trial_period_days' => 14,
                'metadata' => ['company_id' => $company->id, 'tier' => $tier],
            ],
            'metadata' => ['company_id' => $company->id, 'tier' => $tier],
            'allow_promotion_codes' => true,
        ]);

        return [
            'provider' => 'stripe',
            'checkout_url' => $session->url,
            'session_id' => $session->id,
        ];
    }

    /**
     * Swap Stripe subscription plan
     */
    private function swapStripePlan($subscription, string $newTier): bool
    {
        $newPriceId = config("services.stripe.prices.{$newTier}.monthly");

        if (! $newPriceId) {
            throw new \Exception("Stripe price ID not configured for tier: {$newTier}");
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $providerSubId = $subscription->provider_subscription_id ?? null;
        if (! $providerSubId) {
            throw new \Exception('Stripe subscription ID not found');
        }

        $stripeSub = StripeSubscription::retrieve($providerSubId);
        StripeSubscription::update($stripeSub->id, [
            'items' => [[
                'id' => $stripeSub->items->data[0]->id,
                'price' => $newPriceId,
            ]],
            'proration_behavior' => 'create_prorations',
            'metadata' => ['tier' => $newTier],
        ]);

        // Update local records
        if ($subscription->billable instanceof Company) {
            $subscription->billable->update(['subscription_tier' => $newTier]);
        }

        Log::info('Stripe subscription plan swapped', [
            'subscription_id' => $providerSubId,
            'new_tier' => $newTier,
        ]);

        return true;
    }

    /**
     * Cancel Stripe subscription
     */
    private function cancelStripeSubscription($subscription): bool
    {
        $providerSubId = $subscription->provider_subscription_id ?? null;
        if (! $providerSubId) {
            throw new \Exception('Stripe subscription ID not found');
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        StripeSubscription::update($providerSubId, [
            'cancel_at_period_end' => true,
        ]);

        Log::info('Stripe subscription cancelled', [
            'subscription_id' => $providerSubId,
        ]);

        return true;
    }

    /**
     * Get tier pricing
     */
    public static function getTierPrice(string $tier): float
    {
        return self::TIER_PRICING[$tier] ?? 0;
    }

    /**
     * Get all available tiers
     */
    public static function getAvailableTiers(): array
    {
        return [
            'free' => ['price' => 0, 'name' => 'Free'],
            'starter' => ['price' => 12, 'name' => 'Starter'],
            'standard' => ['price' => 29, 'name' => 'Standard'],
            'business' => ['price' => 59, 'name' => 'Business'],
            'max' => ['price' => 149, 'name' => 'Max'],
        ];
    }
} // CLAUDE-CHECKPOINT
