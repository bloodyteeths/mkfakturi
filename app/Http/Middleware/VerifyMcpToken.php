<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * MCP Token Authentication Middleware
 *
 * Verifies that requests to internal MCP endpoints come from the authorized
 * MCP server by checking the Bearer token against the configured MCP_SERVER_TOKEN.
 *
 * This middleware provides security for internal API endpoints that should only
 * be accessible by the MCP server, not by regular users or external clients.
 */
class VerifyMcpToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if MCP feature is enabled
        if (! config('features.mcp_ai_tools.enabled', false)) {
            return response()->json([
                'error' => 'MCP tools disabled',
                'message' => 'The MCP AI tools feature is currently disabled.',
            ], 403);
        }

        // Get the Bearer token from the request
        $token = $request->bearerToken();

        // Get the configured MCP server token
        $expectedToken = config('services.mcp.token');

        // Verify token is present and matches
        if (! $token || $token !== $expectedToken) {
            return response()->json([
                'error' => 'Invalid MCP token',
                'message' => 'Authentication failed. Invalid or missing MCP server token.',
            ], 401);
        }

        // Token is valid, proceed with the request
        return $next($request);
    }
}

// CLAUDE-CHECKPOINT
