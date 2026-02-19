<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Clawd Monitor Token Authentication Middleware
 *
 * Verifies that requests to the Clawd monitoring endpoint come from the
 * authorized Clawd bot by checking X-Monitor-Token header against config.
 * Machine-to-machine auth — NOT Sanctum.
 */
class VerifyClawdToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Monitor-Token');
        $expectedToken = config('services.clawd.monitor_token');

        if (! $token || ! $expectedToken || $token !== $expectedToken) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid or missing monitor token.',
            ], 401);
        }

        return $next($request);
    }
}
