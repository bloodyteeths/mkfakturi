<?php

namespace Modules\Mk\Http\Middleware;

use App\Models\Partner;
use Closure;
use Illuminate\Http\Request;
use Modules\Mk\Partner\Services\PartnerUsageLimitService;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckPartnerLimit Middleware
 *
 * Enforces partner-level usage limits (companies, AI credits, etc.)
 * Only triggers on write operations (POST/PUT/PATCH/DELETE).
 * Super admins bypass all restrictions.
 *
 * Usage:
 *   Route::post('/portfolio/companies', ...)->middleware('partner.limit:companies');
 */
class CheckPartnerLimit
{
    public function handle(Request $request, Closure $next, string $meter): Response
    {
        // Only check write operations
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'])) {
            return $next($request);
        }

        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        // Super admin bypass
        if ($user->role === 'super admin') {
            return $next($request);
        }

        // Only applies to partner users
        if ($user->role !== 'partner') {
            return $next($request);
        }

        $partner = Partner::where('user_id', $user->id)->first();
        if (!$partner) {
            return $next($request);
        }

        $usageService = app(PartnerUsageLimitService::class);

        if (!$usageService->canUse($partner, $meter)) {
            return response()->json(
                $usageService->buildLimitExceededResponse($partner, $meter),
                403
            );
        }

        return $next($request);
    }
}
// CLAUDE-CHECKPOINT
