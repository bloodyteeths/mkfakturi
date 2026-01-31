<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Models\Partner;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PartnerScopeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        \Log::info('PartnerScopeMiddleware START', [
            'url' => $request->url(),
            'method' => $request->method(),
        ]);

        $user = Auth::user();

        if (! $user) {
            \Log::warning('PartnerScopeMiddleware - No authenticated user');

            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Super admin bypasses all partner checks - they have full access
        if ($user->role === 'super admin') {
            \Log::info('PartnerScopeMiddleware - Super admin bypass', ['user_id' => $user->id]);

            // Get company context for super admin
            $companyId = $this->getCurrentCompanyId($request);

            if ($companyId) {
                $company = Company::find($companyId);
                if ($company) {
                    $request->merge([
                        'partner_context' => [
                            'partner_id' => 0,
                            'company_id' => $companyId,
                            'partner' => null,
                            'is_super_admin' => true,
                        ],
                    ]);
                }
            }

            return $next($request);
        }

        // Check if user is a partner
        $partner = Partner::where('user_id', $user->id)->first();

        if (! $partner) {
            \Log::warning('PartnerScopeMiddleware - User is not a partner', ['user_id' => $user->id]);

            return response()->json([
                'error' => 'User is not registered as a partner',
            ], 403);
        }

        \Log::info('PartnerScopeMiddleware - Partner found', [
            'partner_id' => $partner->id,
            'is_active' => $partner->is_active,
        ]);

        // Check if partner is active
        if (! $partner->is_active) {
            \Log::warning('PartnerScopeMiddleware - Partner inactive', ['partner_id' => $partner->id]);

            return response()->json([
                'error' => 'Partner account is inactive',
            ], 403);
        }

        // Get the current company context from session or request
        $companyId = $this->getCurrentCompanyId($request);

        \Log::info('PartnerScopeMiddleware - Company context', [
            'company_id' => $companyId,
            'url' => $request->url(),
        ]);

        // Skip company access check for routes that list all companies
        $skipCompanyCheck = $request->is('api/*/console/companies') ||
                           $request->is('api/*/console') ||
                           $request->is('api/*/partner/dashboard') ||
                           $request->is('api/*/partner/commissions') ||
                           $request->is('api/*/partner/clients') ||
                           $request->is('api/*/partner/referrals') ||
                           $request->is('api/*/partner/payouts') ||
                           $request->is('api/*/partner/stripe-connect/*') ||
                           $request->is('api/*/partner/bank-details');

        if ($companyId && ! $skipCompanyCheck) {
            // Verify partner has access to this company
            $hasAccess = $partner->activeCompanies()
                ->where('companies.id', $companyId)
                ->exists();

            \Log::info('PartnerScopeMiddleware - Access check', [
                'company_id' => $companyId,
                'has_access' => $hasAccess,
            ]);

            if (! $hasAccess) {
                \Log::warning('PartnerScopeMiddleware - BLOCKED: No access to company', [
                    'partner_id' => $partner->id,
                    'company_id' => $companyId,
                    'url' => $request->url(),
                ]);

                return response()->json([
                    'error' => 'Partner does not have access to this company',
                ], 403);
            }
            // Add company context to request
            $request->merge([
                'partner_context' => [
                    'partner_id' => $partner->id,
                    'company_id' => $companyId,
                    'partner' => $partner,
                ],
            ]);
        } elseif ($skipCompanyCheck) {
            \Log::info('PartnerScopeMiddleware - Skipping company check for console/listing route');
        }

        // Add partner info to request for use in controllers
        $request->merge([
            'partner_id' => $partner->id,
            'partner' => $partner,
        ]);

        \Log::info('PartnerScopeMiddleware - Passing to controller');

        return $next($request);
    }

    /**
     * Get the current company ID from request or session
     */
    private function getCurrentCompanyId(Request $request): ?int
    {
        // First, check route parameter (for routes like /companies/{company}/...)
        $companyParam = $request->route('company');
        if ($companyParam) {
            return (int) $companyParam;
        }

        // Then check the company header (frontend sends company context here)
        if ($request->header('company')) {
            return (int) $request->header('company');
        }

        // Check if company_id is in the request body/query (for API calls)
        if ($request->has('company_id')) {
            return (int) $request->input('company_id');
        }

        // Then check session for stored company context
        $partnerContext = session('partner_context');
        if ($partnerContext && isset($partnerContext['company_id'])) {
            return (int) $partnerContext['company_id'];
        }

        // Check if there's a selected company in session
        if (session()->has('partner_selected_company_id')) {
            return (int) session('partner_selected_company_id');
        }

        return null;
    }
}
// CLAUDE-CHECKPOINT
