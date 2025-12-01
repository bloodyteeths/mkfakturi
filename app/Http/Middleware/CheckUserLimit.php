<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Services\UserCountService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckUserLimit Middleware
 *
 * FG-01-30: Enforces user limits based on subscription tier
 *
 * Tier Limits:
 * - Free: 1 user
 * - Starter: 1 user
 * - Standard: 3 users
 * - Business: 5 users
 * - Max: Unlimited
 */
class CheckUserLimit
{
    protected UserCountService $userCountService;

    public function __construct(UserCountService $userCountService)
    {
        $this->userCountService = $userCountService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only enforce limit on user creation
        if ($request->method() !== 'POST') {
            return $next($request);
        }

        // Get company ID from header (set by CompanyMiddleware)
        $companyId = $request->header('company');

        if (! $companyId) {
            return response()->json([
                'error' => 'company_header_missing',
                'message' => 'Company header is required',
            ], 400);
        }

        // Load company with subscription
        $company = Company::with('subscription')->find($companyId);

        if (! $company) {
            return response()->json([
                'error' => 'company_not_found',
                'message' => 'Company not found',
            ], 404);
        }

        // Check if user limit reached
        if ($this->userCountService->hasReachedLimit($company)) {
            $upgradeMessage = $this->userCountService->getUpgradeMessage($company);
            $paddlePriceId = $this->userCountService->getUpgradePriceId($company);

            // Build Paddle checkout URL
            $checkoutUrl = $paddlePriceId
                ? config('services.paddle.checkout_url').'?price_id='.$paddlePriceId
                : null;

            return response()->json([
                'error' => 'user_limit_reached',
                'message' => $upgradeMessage,
                'usage' => $this->userCountService->getUsageStats($company),
                'upgrade' => [
                    'required' => true,
                    'paddle_price_id' => $paddlePriceId,
                    'checkout_url' => $checkoutUrl,
                ],
            ], 402); // 402 Payment Required
        }

        // Limit OK - proceed with request
        return $next($request);
    }
}
// CLAUDE-CHECKPOINT
