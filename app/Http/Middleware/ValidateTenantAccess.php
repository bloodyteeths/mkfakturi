<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ValidateTenantAccess Middleware
 *
 * Validates that the authenticated user has access to the requested company
 * and stores the validated company_id in request attributes for downstream use.
 *
 * IMPORTANT: This middleware does NOT add global scopes to models.
 * Global scopes are dangerous in queue workers because they persist across
 * jobs and cause cross-tenant data leaks. Instead, controllers and services
 * must use explicit BelongsToCompany::forCompany($companyId) scopes.
 *
 * @see P0-13 Tenant Scoping Audit
 * @see \App\Traits\BelongsToCompany
 */
class ValidateTenantAccess
{
    /**
     * Handle an incoming request.
     *
     * Resolves and validates the company_id from the request header,
     * ensures the authenticated user has access to that company,
     * and stores the validated company_id in request attributes.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'error' => 'Unauthenticated',
            ], 401);
        }

        $companyId = $request->header('company');

        if (! $companyId) {
            // Fall back to user's first company
            $firstCompany = $user->companies()->first();

            if (! $firstCompany) {
                return response()->json([
                    'error' => 'No company associated with user',
                ], 403);
            }

            $companyId = $firstCompany->id;
        } else {
            $companyId = (int) $companyId;

            // Validate user has access to this company
            if (! $user->hasCompany($companyId)) {
                return response()->json([
                    'error' => 'Unauthorized access to company',
                ], 403);
            }
        }

        // Store validated company_id in request attributes (NOT as global scope)
        $request->attributes->set('validated_company_id', $companyId);

        return $next($request);
    }
}

// CLAUDE-CHECKPOINT
