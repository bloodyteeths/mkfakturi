<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure Company Subscription Middleware
 *
 * Validates that a company has sufficient subscription level to access a feature
 * Also handles special cases for accountants with partner_tier='plus'
 */
class EnsureCompanySubscription
{
    /**
     * Plan hierarchy for comparison
     */
    private const PLAN_HIERARCHY = [
        'free' => 0,
        'starter' => 1,
        'standard' => 2,
        'business' => 3,
        'max' => 4,
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $minPlan  Minimum plan required (e.g., 'standard')
     */
    public function handle(Request $request, Closure $next, string $minPlan = 'free'): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'error' => 'Unauthenticated',
                'message' => 'You must be logged in to access this resource.',
            ], 401);
        }

        // Super Admin Bypass
        if ($user->role === 'super admin') {
            return $next($request);
        }

        // Special case: Accountants with 'plus' tier get full access for their own office
        if ($this->isAccountantPlusWithAccess($user)) {
            return $next($request);
        }

        // Get company from request header or user context
        $companyId = $request->header('company');

        if (! $companyId) {
            return response()->json([
                'error' => 'Company required',
                'message' => 'No company context found.',
            ], 400);
        }

        $company = Company::find($companyId);

        if (! $company) {
            return response()->json([
                'error' => 'Company not found',
                'message' => 'The specified company does not exist.',
            ], 404);
        }

        // Check if user has access to this company
        if (! $user->hasCompany($companyId)) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'You do not have access to this company.',
            ], 403);
        }

        // Check subscription level
        if (! $this->hasRequiredPlan($company, $minPlan)) {
            return response()->json([
                'error' => 'Upgrade required',
                'message' => "This feature requires a '{$minPlan}' plan or higher.",
                'current_plan' => $company->subscription ? $company->subscription->plan : 'none',
                'required_plan' => $minPlan,
                'upgrade_url' => '/upgrade',
            ], 403);
        }

        return $next($request);
    }

    /**
     * Check if user is an accountant with 'plus' tier who has access
     */
    private function isAccountantPlusWithAccess(User $user): bool
    {
        // Must be an accountant
        if ($user->account_type !== 'accountant') {
            return false;
        }

        // Must have 'plus' tier
        if ($user->partner_tier !== 'plus') {
            return false;
        }

        // Must have verified KYC
        if ($user->kyc_status !== 'verified') {
            return false;
        }

        return true;
    }

    /**
     * Check if company has required plan level
     */
    private function hasRequiredPlan(Company $company, string $minPlan): bool
    {
        // Load subscription if not already loaded
        if (! $company->relationLoaded('subscription')) {
            $company->load('subscription');
        }

        // No subscription = free plan
        if (! $company->subscription) {
            return self::PLAN_HIERARCHY[$minPlan] <= 0; // Only 'free' level features allowed
        }

        // Subscription must be active or in trial
        if (! in_array($company->subscription->status, ['trial', 'active'])) {
            return false;
        }

        $currentPlanLevel = self::PLAN_HIERARCHY[$company->subscription->plan] ?? 0;
        $requiredPlanLevel = self::PLAN_HIERARCHY[$minPlan] ?? 0;

        return $currentPlanLevel >= $requiredPlanLevel;
    }
}
// CLAUDE-CHECKPOINT
