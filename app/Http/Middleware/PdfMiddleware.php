<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PdfMiddleware
{
    /**
     * Рачка со дојдовен барање за PDF
     * Проверка на автентикација преку различни guards
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Провери ако корисникот е автентициран преку било кој guard
        if (Auth::guard('web')->check() || Auth::guard('sanctum')->check() || Auth::guard('customer')->check()) {
            return $next($request);
        }

        // Ако не е автентициран, врати 401 (за iframe барања)
        abort(401, 'Unauthorized');
    }
}
