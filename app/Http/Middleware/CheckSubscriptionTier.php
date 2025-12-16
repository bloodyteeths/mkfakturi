<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckSubscriptionTier Middleware
 *
 * Gates features behind subscription tiers.
 * Can be used with parameter to specify minimum required plan.
 *
 * Usage:
 * - Route::post('/efaktura', ...)->middleware('tier:standard')
 * - Route::post('/bank-connect', ...)->middleware('tier:business')
 */
class CheckSubscriptionTier
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $feature  Feature key or minimum plan
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        // Super Admin Bypass
        if ($user && $user->role === 'super admin') {
            return $next($request);
        }

        // Partner Bypass - partners managing client companies have full access
        // Partners pay for their own tier which covers their managed clients
        if ($user && $user->role === 'partner') {
            $companyId = $request->header('company');
            if ($companyId && $user->hasPartnerAccessToCompany((int) $companyId)) {
                return $next($request);
            }
        }

        // Get current company from request header (set by CompanyMiddleware)
        $companyId = $request->header('company');

        if (! $companyId) {
            return response()->json([
                'error' => 'No company context found',
                'message' => 'You must select a company to access this feature',
            ], 400);
        }

        // Load company with subscription
        $company = \App\Models\Company::with('subscription')->find($companyId);

        if (! $company) {
            return response()->json([
                'error' => 'Company not found',
                'message' => 'The specified company does not exist',
            ], 404);
        }

        // Load subscription if not already loaded
        if (! $company->relationLoaded('subscription')) {
            $company->load('subscription');
        }

        // Determine if feature is a plan name or feature key
        $requiredPlan = $this->getRequiredPlan($feature);

        if (! $requiredPlan) {
            return response()->json([
                'error' => 'Invalid feature configuration',
                'message' => 'Feature or plan not recognized',
            ], 500);
        }

        // Check if company can access this feature
        if (! $this->canAccessFeature($company, $requiredPlan)) {
            $upgradeMessage = $this->getUpgradeMessage($feature, $requiredPlan);
            $upgradePriceId = $this->getUpgradePriceId($requiredPlan);

            return response()->json([
                'error' => 'upgrade_required',
                'message' => $upgradeMessage,
                'feature' => $feature,
                'required_plan' => $requiredPlan,
                'current_plan' => $company->subscription?->plan ?? 'free',
                'upgrade' => [
                    'required' => true,
                    'paddle_price_id' => $upgradePriceId,
                    'checkout_url' => $this->generateCheckoutUrl($upgradePriceId, $company),
                ],
            ], 402); // 402 Payment Required
        }

        // Allow request to proceed
        return $next($request);
    }

    /**
     * Get required plan for a feature
     */
    protected function getRequiredPlan(string $feature): ?string
    {
        $planHierarchy = config('subscriptions.plan_hierarchy', []);

        // If feature is already a plan name, return it
        if (array_key_exists($feature, $planHierarchy)) {
            return $feature;
        }

        // Otherwise, look up feature requirement
        return config("subscriptions.feature_requirements.{$feature}");
    }

    /**
     * Check if company can access a feature
     *
     * @param  \App\Models\Company  $company
     */
    protected function canAccessFeature($company, string $requiredPlan): bool
    {
        $planHierarchy = config('subscriptions.plan_hierarchy', []);

        // Get current plan (default to free if no subscription)
        $currentPlan = $company->subscription?->plan ?? 'free';

        // Check if subscription is active
        if ($company->subscription && ! $company->subscription->isActive()) {
            $currentPlan = 'free'; // Inactive subscription = free tier
        }

        // Check trial status - if on trial, use the trial plan
        if ($company->subscription && $company->subscription->onTrial()) {
            $trialPlan = config('subscriptions.trial.plan', 'standard');
            $currentPlan = $trialPlan;
        }

        $currentLevel = $planHierarchy[$currentPlan] ?? 0;
        $requiredLevel = $planHierarchy[$requiredPlan] ?? 0;

        return $currentLevel >= $requiredLevel;
    }

    /**
     * Get upgrade message for a feature
     */
    protected function getUpgradeMessage(string $feature, string $requiredPlan): string
    {
        // Check for feature-specific message
        $featureMessage = config("subscriptions.upgrade_messages.{$feature}");

        if ($featureMessage) {
            return $featureMessage;
        }

        // Generic message
        $planName = config("subscriptions.tiers.{$requiredPlan}.name", ucfirst($requiredPlan));

        return "This feature requires a {$planName} plan or higher. Please upgrade to continue.";
    }

    /**
     * Get Paddle price ID for required plan
     */
    protected function getUpgradePriceId(string $requiredPlan): ?string
    {
        return config("subscriptions.paddle_prices.{$requiredPlan}");
    }

    /**
     * Generate Paddle checkout URL for upgrade
     *
     * @param  \App\Models\Company  $company
     */
    protected function generateCheckoutUrl(?string $priceId, $company): ?string
    {
        if (! $priceId) {
            return null;
        }

        $paddleEnvironment = config('services.paddle.environment', 'sandbox');
        $paddleBaseUrl = $paddleEnvironment === 'production'
            ? 'https://checkout.paddle.com'
            : 'https://sandbox-checkout.paddle.com';

        // Build checkout URL with prefilled customer data
        $params = http_build_query([
            'price_id' => $priceId,
            'customer_email' => $company->owner->email ?? '',
            'customer_company' => $company->name,
            'passthrough' => json_encode([
                'company_id' => $company->id,
                'source' => 'feature_gate',
            ]),
        ]);

        return "{$paddleBaseUrl}/checkout?{$params}";
    }
}
// CLAUDE-CHECKPOINT
