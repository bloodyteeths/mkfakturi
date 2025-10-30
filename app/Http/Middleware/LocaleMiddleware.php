<?php

namespace App\Http\Middleware;

use App\Models\CompanySetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LocaleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated and has a company
        if (Auth::check() && $request->header('company')) {
            $companyId = $request->header('company');
            
            // Get the language setting for the company
            $locale = CompanySetting::getSetting('language', $companyId);
            
            if ($locale) {
                // Set the Laravel application locale
                App::setLocale($locale);
            }
        }

        return $next($request);
    }
}