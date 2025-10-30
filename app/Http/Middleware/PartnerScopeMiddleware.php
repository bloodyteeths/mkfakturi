<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Partner;
use App\Models\Company;
use Symfony\Component\HttpFoundation\Response;

class PartnerScopeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check if user is a partner
        $partner = Partner::where('user_id', $user->id)->first();
        
        if (!$partner) {
            return response()->json([
                'error' => 'User is not registered as a partner'
            ], 403);
        }

        // Check if partner is active
        if (!$partner->is_active) {
            return response()->json([
                'error' => 'Partner account is inactive'
            ], 403);
        }

        // Get the current company context from session or request
        $companyId = $this->getCurrentCompanyId($request);
        
        if ($companyId) {
            // Verify partner has access to this company
            $hasAccess = $partner->activeCompanies()
                ->where('companies.id', $companyId)
                ->exists();
                
            if (!$hasAccess) {
                return response()->json([
                    'error' => 'Partner does not have access to this company'
                ], 403);
            }

            // Add company context to request
            $request->merge([
                'partner_context' => [
                    'partner_id' => $partner->id,
                    'company_id' => $companyId,
                    'partner' => $partner,
                ]
            ]);
        }

        // Add partner info to request for use in controllers
        $request->merge([
            'partner_id' => $partner->id,
            'partner' => $partner,
        ]);

        return $next($request);
    }

    /**
     * Get the current company ID from request or session
     */
    private function getCurrentCompanyId(Request $request): ?int
    {
        // First, check if company_id is in the request (for API calls)
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

