<?php

namespace Modules\Mk\Bitrix\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bitrix Auth Middleware
 *
 * Validates X-Bitrix-Secret header against BITRIX_SHARED_SECRET env variable.
 * Used to protect Bitrix API endpoints from unauthorized access.
 */
class BitrixAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $sharedSecret = config('services.bitrix.shared_secret');

        // Check if shared secret is configured
        if (empty($sharedSecret)) {
            Log::warning('Bitrix shared secret not configured', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Bitrix integration not configured',
            ], 503);
        }

        // Get secret from header
        $providedSecret = $request->header('X-Bitrix-Secret');

        // Validate secret
        if (! $providedSecret || ! hash_equals($sharedSecret, $providedSecret)) {
            Log::warning('Bitrix auth failed: invalid secret', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        return $next($request);
    }
}

// CLAUDE-CHECKPOINT
