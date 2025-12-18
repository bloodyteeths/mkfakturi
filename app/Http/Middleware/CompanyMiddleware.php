<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class CompanyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip company middleware for console routes (partner-only routes)
        // Partners have their own authorization via PartnerScopeMiddleware
        if ($request->is('api/v1/console') || $request->is('api/v1/console/*')) {
            return $next($request);
        }

        if (Schema::hasTable('user_company')) {
            $user = $request->user();

            // Handle partner users - they access client companies via partner_company_links
            if ($user && $user->role === 'partner') {
                $companyId = $request->header('company');

                if ($companyId) {
                    // Verify partner has access to this company
                    $partner = $user->partner;
                    if ($partner) {
                        $hasAccess = \App\Models\PartnerCompanyLink::where('partner_id', $partner->id)
                            ->where('company_id', $companyId)
                            ->where('is_active', true)
                            ->exists();

                        if (!$hasAccess) {
                            \Log::warning('Partner attempted to access unauthorized company', [
                                'user_id' => $user->id,
                                'partner_id' => $partner->id,
                                'company_id' => $companyId,
                            ]);
                            return response()->json(['error' => 'Unauthorized access to company'], 403);
                        }

                        // Load company with IFRS entity for partner access
                        $company = Company::with('ifrsEntity')->find($companyId);
                        if ($company && $company->ifrsEntity) {
                            $user->setRelation('entity', $company->ifrsEntity);
                        }
                    }
                }

                return $next($request);
            }

            // Only proceed if user has companies
            $firstCompany = $user->companies()->first();
            if (! $firstCompany) {
                return $next($request);
            }

            if (! $request->header('company')) {
                $request->headers->set('company', $firstCompany->id);
            } elseif (! $user->hasCompany($request->header('company'))) {
                // Instead of 403, gracefully fallback to user's first company
                // This handles cases where user lost access or company was deleted
                \Log::warning('User attempted to access unauthorized company, falling back to first company', [
                    'user_id' => $user->id,
                    'attempted_company' => $request->header('company'),
                    'fallback_company' => $firstCompany->id,
                ]);
                $request->headers->set('company', $firstCompany->id);
            }

            // CLAUDE-CHECKPOINT: Hydrate user's IFRS entity from company
            // Get the company ID from the request header
            $companyId = $request->header('company');

            if ($companyId) {
                // Load the company with its IFRS entity relationship
                $company = Company::with('ifrsEntity')->find($companyId);

                // If company exists and has an IFRS entity, set it on the user
                if ($company && $company->ifrsEntity) {
                    // Use setRelation to avoid unnecessary DB writes
                    $user->setRelation('entity', $company->ifrsEntity);
                }
                // If company has no entity, we allow to continue (graceful handling)
            }
        }

        return $next($request);
    }
}
