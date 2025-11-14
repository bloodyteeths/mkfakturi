<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string[]  ...$guards
     * @return mixed
     */
    public function handle($request, $next, ...$guards)
    {
        \Log::info('Authenticate middleware handling request', [
            'url' => $request->url(),
            'method' => $request->method(),
            'guards' => $guards,
            'authenticated_before' => auth()->check(),
            'user_id_before' => auth()->id(),
            'session_id' => session()->getId(),
            'has_session_cookie' => $request->hasCookie(config('session.cookie')),
        ]);

        $result = parent::handle($request, $next, ...$guards);

        \Log::info('Authenticate middleware completed', [
            'authenticated_after' => auth()->check(),
            'user_id_after' => auth()->id(),
        ]);

        return $result;
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function redirectTo($request)
    {
        \Log::warning('Authenticate middleware: User not authenticated, redirecting', [
            'url' => $request->url(),
            'expects_json' => $request->expectsJson(),
            'session_id' => session()->getId(),
        ]);

        if (! $request->expectsJson()) {
            return route('login');
        }
    }
}
