<?php

namespace App\Http\Middleware;

use App\Services\InvoiceCountService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckInvoiceLimit Middleware
 *
 * Enforces invoice creation limits based on subscription tier.
 * Returns 402 Payment Required if company has reached their monthly limit.
 *
 * Usage: Apply to invoice creation routes only
 */
class CheckInvoiceLimit
{
    protected InvoiceCountService $invoiceCountService;

    public function __construct(InvoiceCountService $invoiceCountService)
    {
        $this->invoiceCountService = $invoiceCountService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get current company from request header (set by CompanyMiddleware)
        $companyId = $request->header('company');

        if (! $companyId) {
            return response()->json([
                'error' => 'No company context found',
                'message' => 'You must select a company to create invoices',
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

        // Check if company has reached their invoice limit
        if ($this->invoiceCountService->hasReachedLimit($company)) {
            $usageStats = $this->invoiceCountService->getUsageStats($company);
            $upgradeMessage = $this->invoiceCountService->getUpgradeMessage($company);
            $upgradePriceId = $this->invoiceCountService->getUpgradePriceId($company);

            return response()->json([
                'error' => 'invoice_limit_reached',
                'message' => $upgradeMessage,
                'usage' => $usageStats,
                'upgrade' => [
                    'required' => true,
                    'paddle_price_id' => $upgradePriceId,
                    'checkout_url' => $this->generateCheckoutUrl($upgradePriceId, $company),
                ],
            ], 402); // 402 Payment Required
        }

        // Allow request to proceed
        $response = $next($request);

        // After successful invoice creation, increment the cache
        if ($response->isSuccessful() && $request->isMethod('post')) {
            $this->invoiceCountService->incrementCache($company->id);
        }

        return $response;
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
                'source' => 'invoice_limit',
            ]),
        ]);

        return "{$paddleBaseUrl}/checkout?{$params}";
    }
}
// CLAUDE-CHECKPOINT
