<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Feature Flag Middleware
 *
 * Checks if a specific feature is enabled via config/features.php
 * Aborts with 403 if feature is disabled.
 */
class FeatureMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $feature  The feature name to check (e.g., 'partner_portal', 'stock')
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        // Check if feature is enabled in config/features.php
        $featureEnabled = config("features.{$feature}.enabled", false);

        // Also check config/facturino.php for Facturino-specific features
        if (! $featureEnabled) {
            $featureEnabled = config("facturino.features.{$feature}", false);
        }

        if (! $featureEnabled) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => "The '{$feature}' feature is currently disabled.",
            ], 403);
        }

        return $next($request);
    }
}

// CLAUDE-CHECKPOINT
