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

            // Only proceed if user has companies
            $firstCompany = $user->companies()->first();
            if (!$firstCompany) {
                return $next($request);
            }

            if (! $request->header('company')) {
                $request->headers->set('company', $firstCompany->id);
            } elseif (! $user->hasCompany($request->header('company'))) {
                abort(403, 'Unauthorized company context.');
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
