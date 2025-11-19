<?php

namespace App\Http\Middleware;

use App\Enums\PartnerPermission;
use App\Models\Partner;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to check if partner has specific permission for a company
 * Usage: ->middleware('partner.permission:view_invoices')
 */
class CheckPartnerPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission  The required permission (e.g., 'view_invoices')
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        // Non-partner users (super admin, regular users) bypass this check
        if (! $user || $user->role !== 'partner') {
            return $next($request);
        }

        // Get the partner record
        $partner = Partner::where('user_id', $user->id)->first();

        if (! $partner) {
            return response()->json([
                'message' => 'Not registered as partner',
            ], 403);
        }

        // Get company ID from request
        // Check multiple sources: header, route parameter, request body
        $companyId = $request->header('company')
            ?? $request->route('company')
            ?? $request->input('company_id');

        if (! $companyId) {
            return response()->json([
                'message' => 'Company context required',
            ], 400);
        }

        // Verify partner has access to this company
        $hasCompanyAccess = $partner->companies()
            ->where('companies.id', $companyId)
            ->where('partner_company_links.is_active', true)
            ->exists();

        if (! $hasCompanyAccess) {
            return response()->json([
                'message' => 'No access to this company',
            ], 403);
        }

        // Convert string permission to enum
        try {
            $permissionEnum = PartnerPermission::from($permission);
        } catch (\ValueError $e) {
            \Log::error('Invalid permission in middleware', [
                'permission' => $permission,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Invalid permission configuration',
            ], 500);
        }

        // Check if partner has the required permission
        if (! $partner->hasPermission($companyId, $permissionEnum)) {
            return response()->json([
                'message' => 'Insufficient permissions',
                'required_permission' => $permissionEnum->label(),
            ], 403);
        }

        return $next($request);
    }
}

// CLAUDE-CHECKPOINT
