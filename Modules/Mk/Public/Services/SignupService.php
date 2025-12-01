<?php

namespace Modules\Mk\Public\Services;

use App\Models\AffiliateLink;
use App\Models\Company;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Support\Facades\DB;
// Hash not needed - User model has setPasswordAttribute mutator
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * SignupService
 *
 * Handles business logic for public company signup with referral tracking
 */
class SignupService
{
    /**
     * Validate referral code and return partner information
     *
     * @param  string  $code  Referral code from affiliate link
     * @return array|null Partner information or null if invalid
     */
    public function validateReferralCode(string $code): ?array
    {
        $affiliateLink = AffiliateLink::where('code', $code)
            ->where('is_active', true)
            ->with('partner')
            ->first();

        if (! $affiliateLink || ! $affiliateLink->partner || ! $affiliateLink->partner->is_active) {
            return null;
        }

        // Increment click counter
        $affiliateLink->increment('clicks');

        return [
            'partner_id' => $affiliateLink->partner_id,
            'partner_name' => $affiliateLink->partner->name,
            'partner_company' => $affiliateLink->partner->company_name,
            'affiliate_link_id' => $affiliateLink->id,
        ];
    }

    /**
     * Get available Stripe subscription plans
     *
     * @return array List of plans with pricing
     */
    public function getAvailablePlans(): array
    {
        $stripePrices = config('services.stripe.prices');
        $currency = config('services.stripe.currency', 'mkd');

        // Pricing in MKD (monthly prices)
        $pricing = [
            'free' => 0,
            'starter' => 590,
            'standard' => 1490,
            'business' => 2990,
            'max' => 7490,
        ];

        $plans = [
            [
                'id' => 'free',
                'name' => 'Бесплатен',
                'description' => 'Започнете бесплатно',
                'price' => $pricing['free'],
                'price_yearly' => 0,
                'stripe_price_id' => null,
                'currency' => strtoupper($currency),
                'features' => [
                    'До 5 фактури месечно',
                    'Основни извештаи',
                    'Email поддршка',
                ],
            ],
            [
                'id' => 'starter',
                'name' => 'Starter',
                'description' => 'За мали бизниси',
                'price' => $pricing['starter'],
                'price_yearly' => $pricing['starter'] * 10,
                'stripe_price_id' => $stripePrices['starter']['monthly'] ?? null,
                'currency' => strtoupper($currency),
                'features' => [
                    'До 100 фактури месечно',
                    'Проценки и плаќања',
                    'Email поддршка',
                ],
            ],
            [
                'id' => 'standard',
                'name' => 'Standard',
                'description' => 'За растечки бизниси',
                'price' => $pricing['standard'],
                'price_yearly' => $pricing['standard'] * 10,
                'stripe_price_id' => $stripePrices['standard']['monthly'] ?? null,
                'currency' => strtoupper($currency),
                'features' => [
                    'До 500 фактури месечно',
                    'Е-фактура (UBL XML)',
                    'Рекурентни фактури',
                    'Приоритетна поддршка',
                ],
            ],
            [
                'id' => 'business',
                'name' => 'Business',
                'description' => 'Напредни функции',
                'price' => $pricing['business'],
                'price_yearly' => $pricing['business'] * 10,
                'stripe_price_id' => $stripePrices['business']['monthly'] ?? null,
                'currency' => strtoupper($currency),
                'features' => [
                    'Неограничени фактури',
                    'PSD2 банкарски конекции',
                    'Повеќе валути',
                    'API пристап',
                    'Премиум поддршка',
                ],
            ],
            [
                'id' => 'max',
                'name' => 'Max',
                'description' => 'Сè што ви треба',
                'price' => $pricing['max'],
                'price_yearly' => $pricing['max'] * 10,
                'stripe_price_id' => $stripePrices['max']['monthly'] ?? null,
                'currency' => strtoupper($currency),
                'features' => [
                    'Сите Business функции',
                    'IFRS сметководство',
                    'White-label опции',
                    'Посветена поддршка',
                    'Прилагодени интеграции',
                ],
            ],
        ];

        return $plans;
    }

    /**
     * Register new company with user and optional referral
     *
     * @param  array  $data  Registration data
     * @return array Company and checkout session information
     *
     * @throws \Exception
     */
    public function register(array $data): array
    {
        try {
            DB::beginTransaction();

            // Create company
            $company = $this->createCompany($data);

            // Create admin user
            $user = $this->createAdminUser($data, $company);

            // Record referral conversion if applicable
            if (! empty($data['affiliate_link_id'])) {
                $this->recordConversion($data['affiliate_link_id'], $company->id);
            }

            $plan = $data['plan'] ?? 'free';
            $checkoutUrl = null;
            $checkoutSessionId = null;

            // Free plan - no checkout needed
            if ($plan === 'free') {
                // Set company subscription tier to free
                $company->update([
                    'subscription_tier' => 'free',
                    'subscription_status' => 'active',
                ]);

                // Login URL for free plan
                $checkoutUrl = config('app.url').'/login?registered=1&email='.urlencode($data['email']);
            } else {
                // Create Stripe Checkout session for paid plans
                $checkoutSession = $this->createStripeCheckoutSession($company, $data);
                $checkoutUrl = $checkoutSession->url;
                $checkoutSessionId = $checkoutSession->id;
            }

            DB::commit();

            Log::info('Company registration successful', [
                'company_id' => $company->id,
                'user_id' => $user->id,
                'partner_id' => $data['partner_id'] ?? null,
                'plan' => $plan,
            ]);

            return [
                'company' => $company,
                'user' => $user,
                'checkout_url' => $checkoutUrl,
                'checkout_session_id' => $checkoutSessionId,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Company registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Create company record
     *
     * @param  array  $data  Company data
     */
    private function createCompany(array $data): Company
    {
        $slug = Str::slug($data['company_name']);
        $originalSlug = $slug;
        $counter = 1;

        // Ensure unique slug
        while (Company::where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        $company = Company::create([
            'name' => $data['company_name'],
            'slug' => $slug,
            'vat_number' => $data['vat_number'] ?? null,
            'tax_id' => $data['tax_id'] ?? null,
        ]);

        // Link to partner if referred
        if (! empty($data['partner_id'])) {
            $company->partners()->attach($data['partner_id'], [
                'is_primary' => true,
                'is_active' => true,
                'permissions' => json_encode(['view_reports', 'manage_invoices']),
            ]);
        }

        // Setup default data (roles, payment methods, units, settings)
        $company->setupDefaultData();

        return $company;
    }

    /**
     * Create admin user for company
     *
     * @param  array  $data  User data
     * @param  Company  $company  Company instance
     */
    private function createAdminUser(array $data, Company $company): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'], // User model has setPasswordAttribute that hashes
            'role' => 'super admin',
        ]);

        // Attach user to company
        $user->companies()->attach($company->id);

        // Set company as owner
        $company->update(['owner_id' => $user->id]);

        // Assign super admin role within company scope
        \Silber\Bouncer\BouncerFacade::scope()->to($company->id);
        \Silber\Bouncer\BouncerFacade::assign('super admin')->to($user);

        return $user;
    }

    /**
     * Record conversion for affiliate link
     *
     * @param  int  $affiliateLinkId  Affiliate link ID
     * @param  int  $companyId  Company ID
     */
    private function recordConversion(int $affiliateLinkId, int $companyId): void
    {
        $affiliateLink = AffiliateLink::find($affiliateLinkId);

        if ($affiliateLink) {
            // Increment conversions counter
            $affiliateLink->recordConversion();

            Log::info('Referral conversion recorded', [
                'affiliate_link_id' => $affiliateLinkId,
                'partner_id' => $affiliateLink->partner_id,
                'company_id' => $companyId,
            ]);
        }
    }

    /**
     * Create Stripe Checkout session for subscription
     *
     * @param  Company  $company  Company instance
     * @param  array  $data  Checkout data
     *
     * @throws \Stripe\Exception\ApiErrorException
     */
    private function createStripeCheckoutSession(Company $company, array $data): \Stripe\Checkout\Session
    {
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        $plan = $data['plan'] ?? 'starter';
        $billingPeriod = $data['billing_period'] ?? 'monthly'; // monthly or yearly

        $prices = config('services.stripe.prices');
        $priceId = $prices[$plan][$billingPeriod] ?? null;

        if (! $priceId) {
            throw new \Exception("Invalid plan or billing period: {$plan}/{$billingPeriod}");
        }

        $successUrl = config('app.url').'/signup/success?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = config('app.url').'/signup/cancel';

        $metadata = [
            'company_id' => $company->id,
        ];

        if (! empty($data['partner_id'])) {
            $metadata['partner_id'] = $data['partner_id'];
        }

        if (! empty($data['affiliate_link_id'])) {
            $metadata['affiliate_link_id'] = $data['affiliate_link_id'];
        }

        $session = \Stripe\Checkout\Session::create([
            'mode' => 'subscription',
            'customer_email' => $data['email'],
            'line_items' => [
                [
                    'price' => $priceId,
                    'quantity' => 1,
                ],
            ],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => $metadata,
            'subscription_data' => [
                'metadata' => $metadata,
                'trial_period_days' => 14, // 14-day trial
            ],
        ]);

        return $session;
    }
}

// CLAUDE-CHECKPOINT
