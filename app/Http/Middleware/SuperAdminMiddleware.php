<?php

namespace App\Http\Middleware;

use Auth;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    /**
     * Handle an incoming request.
     * Only allow super admins (role = 'super admin')
     *
     * @param  null  $guard
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $guard = null): Response
    {
        if (Auth::guard($guard)->guest()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = Auth::user();

        // Only super admin role is allowed (not regular admin or partner)
        if ($user->role !== 'super admin') {
            return response()->json(['error' => 'Forbidden - Super admin access required'], 403);
        }

        return $next($request);
    }
}

// CLAUDE-CHECKPOINT
