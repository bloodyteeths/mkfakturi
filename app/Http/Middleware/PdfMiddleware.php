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
     * PDF линковите се ЈАВНИ (public) за споделување со клиенти.
     * Безбедноста е обезбедена преку unique_hash во URL-то.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next): Response
    {
        // PDF линковите се јавно достапни за споделување со клиенти
        // Безбедноста е обезбедена преку unique_hash во URL-то кој е тежок за погодување
        // Ова овозможува клиентите да гледаат фактури без логирање
        return $next($request);
    }
}
