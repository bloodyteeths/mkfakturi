<?php

namespace App\Http\Middleware;

use App\Services\UsageLimitService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckAiFeature Middleware
 *
 * Gates AI features behind subscription tiers using the ai_features config.
 * Partners and super admins bypass all restrictions.
 *
 * Usage:
 *   Route::post('/ai/reconciliation/suggest', ...)->middleware('ai.feature:ai_reconciliation_suggest');
 *   Route::post('/ai/assistant', ...)->middleware('ai.feature:nl_assistant');
 */
class CheckAiFeature
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        // Super Admin bypass
        if ($user && $user->role === 'super admin') {
            return $next($request);
        }

        // Partner: bypass company-level tier check but track AI credits
        if ($user && $user->role === 'partner') {
            $companyId = $request->header('company');
            if ($companyId && $user->hasPartnerAccessToCompany((int) $companyId)) {
                $partner = $user->partner;
                if ($partner) {
                    $partnerUsage = app(\Modules\Mk\Partner\Services\PartnerUsageLimitService::class);
                    if (!$partnerUsage->canUse($partner, 'ai_credits_per_month')) {
                        $response = $partnerUsage->buildLimitExceededResponse($partner, 'ai_credits_per_month');
                        return response()->json($response, 403);
                    }
                    $partnerUsage->incrementUsage($partner, 'ai_credits_per_month');
                }
                return $next($request);
            }
        }

        $companyId = $request->header('company');
        if (! $companyId) {
            return response()->json([
                'error' => 'No company context found',
                'message' => 'You must select a company to access this feature.',
            ], 400);
        }

        $company = \App\Models\Company::find($companyId);
        if (! $company) {
            return response()->json([
                'error' => 'Company not found',
                'message' => 'The specified company does not exist.',
            ], 404);
        }

        $usageService = app(UsageLimitService::class);

        if (! $usageService->canUseAiFeature($company, $feature)) {
            $tier = $usageService->getCompanyTier($company);
            $requiredTier = $this->getRequiredTier($feature);
            $tierName = config("subscriptions.tiers.{$requiredTier}.name", ucfirst($requiredTier));

            return response()->json([
                'error' => 'ai_feature_unavailable',
                'message' => "AI {$this->getFeatureLabel($feature)} requires a {$tierName} plan or higher. Upgrade to unlock this feature.",
                'feature' => $feature,
                'current_tier' => $tier,
                'required_tier' => $requiredTier,
                'upgrade_url' => '/admin/pricing',
            ], 402);
        }

        return $next($request);
    }

    /**
     * Get the minimum tier that includes this AI feature.
     */
    protected function getRequiredTier(string $feature): string
    {
        $aiFeatures = config('subscriptions.ai_features', []);
        $hierarchy = ['free', 'accountant_basic', 'starter', 'standard', 'business', 'max'];

        foreach ($hierarchy as $tier) {
            $features = $aiFeatures[$tier] ?? [];
            if (in_array('*', $features) || in_array($feature, $features)) {
                return $tier;
            }
        }

        return 'max';
    }

    /**
     * Human-readable label for an AI feature.
     */
    protected function getFeatureLabel(string $feature): string
    {
        $labels = [
            'document_classify' => 'document classification',
            'document_extract' => 'document extraction',
            'document_confirm' => 'document confirmation',
            'ai_reconciliation_suggest' => 'reconciliation suggestions',
            'ai_reconciliation_categorize' => 'transaction categorization',
            'nl_assistant' => 'assistant',
        ];

        return $labels[$feature] ?? $feature;
    }
}
// CLAUDE-CHECKPOINT
