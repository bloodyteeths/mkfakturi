<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckViewOnlyMode Middleware
 *
 * Enforces view-only mode for portfolio-managed companies on accountant_basic tier.
 * These companies can VIEW all data but cannot create, edit, or delete records.
 *
 * Usage: Applied to company-scoped API routes that modify data.
 * GET requests always pass through. POST/PUT/PATCH/DELETE are blocked for view-only companies.
 */
class CheckViewOnlyMode
{
    /**
     * HTTP methods that are read-only (always allowed).
     */
    protected array $readOnlyMethods = ['GET', 'HEAD', 'OPTIONS'];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Read-only requests always pass through
        if (in_array($request->method(), $this->readOnlyMethods)) {
            return $next($request);
        }

        $user = $request->user();

        // Super admins bypass
        if ($user && $user->role === 'super admin') {
            return $next($request);
        }

        // Get company context
        $companyId = $request->header('company');
        if (! $companyId) {
            return $next($request);
        }

        $company = \App\Models\Company::find($companyId);
        if (! $company) {
            return $next($request);
        }

        // Only applies to portfolio-managed companies
        if (! $company->is_portfolio_managed || ! $company->managing_partner_id) {
            return $next($request);
        }

        // Partners always have full access to their managed companies
        if ($user && $user->role === 'partner') {
            return $next($request);
        }

        // Company owner of a portfolio-managed company: view-only + AI chat
        if ($user && $user->role !== 'partner' && $company->owner_id === $user->id) {
            // Allow AI chat routes
            if ($this->isAiChatRoute($request)) {
                return $next($request);
            }

            return response()->json([
                'error' => 'view_only_mode',
                'message' => 'Your company is managed by an accountant. You can view all data and use AI chat, but cannot make changes. Subscribe independently to unlock full access.',
                'current_tier' => 'managed',
                'can_use_ai_chat' => true,
                'upgrade_url' => '/admin/pricing',
            ], 403);
        }

        // Check the effective tier for this company
        $effectiveTier = $this->getEffectiveTier($company);

        // accountant_basic = view-only mode
        if ($effectiveTier === 'accountant_basic') {
            return response()->json([
                'error' => 'view_only_mode',
                'message' => 'This company is in view-only mode. You can view all data but cannot make changes. Ask the company owner to subscribe to unlock full access.',
                'current_tier' => 'accountant_basic',
                'upgrade_required' => true,
            ], 403);
        }

        return $next($request);
    }

    /**
     * Get the effective tier for a portfolio-managed company.
     */
    protected function getEffectiveTier(\App\Models\Company $company): string
    {
        // Check if company has its own active paid subscription
        if ($company->subscription
            && in_array($company->subscription->status, ['trial', 'active'])
            && $company->subscription->plan !== 'free') {
            return $company->subscription->plan;
        }

        // Check portfolio tier override
        $tierOverride = DB::table('partner_company_links')
            ->where('company_id', $company->id)
            ->where('partner_id', $company->managing_partner_id)
            ->where('is_portfolio_managed', true)
            ->value('portfolio_tier_override');

        return $tierOverride ?? config('subscriptions.portfolio.uncovered_tier', 'accountant_basic');
    }

    /**
     * Check if the current request is an AI chat route (allowed for view-only company owners).
     */
    protected function isAiChatRoute(Request $request): bool
    {
        $path = $request->path();

        return str_contains($path, 'ai/assistant')
            || str_contains($path, 'ai/chat')
            || str_contains($path, 'nl-assistant');
    }
}
// CLAUDE-CHECKPOINT
