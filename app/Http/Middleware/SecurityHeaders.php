<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security Headers Middleware (SEC-01-02)
 *
 * Adds comprehensive security headers to all responses to protect against
 * common web vulnerabilities including XSS, clickjacking, and MIME-sniffing.
 */
class SecurityHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Content Security Policy (CSP)
        // Allows self + inline scripts/styles for Vue.js compatibility
        // Add specific domains as needed for external resources
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'", // unsafe-eval needed for Vue.js dev
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: https:",
            "font-src 'self' data:",
            "connect-src 'self' https://api.postmarkapp.com",
            "frame-src 'self' blob:", // Allow frames from same origin and blob URLs for email preview
            "frame-ancestors 'self'", // Allow being framed by same origin
            "base-uri 'self'",
            "form-action 'self'",
        ]);

        $response->headers->set('Content-Security-Policy', $csp);

        // Prevent clickjacking attacks - allow same-origin framing
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Prevent MIME-sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Force HTTPS (HSTS) - 1 year, include subdomains
        // Only enable in production with valid SSL certificate
        if (config('app.env') === 'production') {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Use a strict referrer policy that still allows Sanctum
        // to detect same-origin frontend requests for stateful auth
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Feature Policy / Permissions Policy
        // Disable unnecessary browser features
        $response->headers->set('Permissions-Policy', implode(', ', [
            'geolocation=()',
            'microphone=()',
            'camera=()',
            'payment=()',
            'usb=()',
        ]));

        // XSS Protection (legacy browsers)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        return $response;
        // CLAUDE-CHECKPOINT
    }
}
