<?php

namespace Modules\Mk\Public\Services;

use App\Models\AffiliateLink;
use App\Models\Company;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
        $prices = config('services.stripe.prices');
        $currency = config('services.stripe.currency', 'mkd');

        $plans = [
            [
                'id' => 'starter',
                'name' => 'Starter',
                'description' => 'Perfect for small businesses',
                'price_monthly' => $prices['starter']['monthly'] ?? null,
                'price_yearly' => $prices['starter']['yearly'] ?? null,
                'currency' => strtoupper($currency),
                'features' => [
                    'Up to 100 invoices per month',
                    'Basic estimates and payments',
                    'Email support',
                ],
            ],
            [
                'id' => 'standard',
                'name' => 'Standard',
                'description' => 'For growing businesses',
                'price_monthly' => $prices['standard']['monthly'] ?? null,
                'price_yearly' => $prices['standard']['yearly'] ?? null,
                'currency' => strtoupper($currency),
                'features' => [
                    'Up to 500 invoices per month',
                    'E-Invoice (UBL XML)',
                    'Recurring invoices',
                    'Priority support',
                ],
            ],
            [
                'id' => 'business',
                'name' => 'Business',
                'description' => 'Advanced features for professionals',
                'price_monthly' => $prices['business']['monthly'] ?? null,
                'price_yearly' => $prices['business']['yearly'] ?? null,
                'currency' => strtoupper($currency),
                'features' => [
                    'Unlimited invoices',
                    'PSD2 bank connections',
                    'Multi-currency support',
                    'API access',
                    'Premium support',
                ],
            ],
            [
                'id' => 'max',
                'name' => 'Max',
                'description' => 'Everything you need',
                'price_monthly' => $prices['max']['monthly'] ?? null,
                'price_yearly' => $prices['max']['yearly'] ?? null,
                'currency' => strtoupper($currency),
                'features' => [
                    'All Business features',
                    'IFRS accounting',
                    'White-label options',
                    'Dedicated support',
                    'Custom integrations',
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

            // Create Stripe Checkout session
            $checkoutSession = $this->createStripeCheckoutSession($company, $data);

            DB::commit();

            Log::info('Company registration successful', [
                'company_id' => $company->id,
                'user_id' => $user->id,
                'partner_id' => $data['partner_id'] ?? null,
            ]);

            return [
                'company' => $company,
                'user' => $user,
                'checkout_url' => $checkoutSession->url,
                'checkout_session_id' => $checkoutSession->id,
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
            'password' => Hash::make($data['password']),
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
