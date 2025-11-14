<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use App\Models\AffiliateLink;

class CaptureReferral
{
    /**
     * Handle an incoming request.
     *
     * Capture referral codes from query parameters and store them in session/cookie
     * for later attribution during user registration.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $refParam = config('affiliate.ref_param', 'ref');
        $refCode = $request->query($refParam);

        if ($refCode) {
            // Validate that the referral code exists
            $affiliateLink = AffiliateLink::where('code', $refCode)
                ->where('is_active', true)
                ->first();

            if ($affiliateLink) {
                // Store in session for immediate use
                $request->session()->put(config('affiliate.ref_session_key', 'referral_code'), $refCode);
                $request->session()->put('referral_partner_id', $affiliateLink->partner_id);
                $request->session()->put('referral_captured_at', now());

                // Increment click counter
                $affiliateLink->increment('clicks');

                // Log referral capture
                Log::info('Referral captured', [
                    'code' => $refCode,
                    'partner_id' => $affiliateLink->partner_id,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                // Set cookie for longer-term tracking (30 days by default)
                $cookieName = config('affiliate.ref_cookie', 'facturino_ref');
                $cookieLifetime = config('affiliate.ref_cookie_lifetime', 43200); // 30 days in minutes

                Cookie::queue(
                    $cookieName,
                    $refCode,
                    $cookieLifetime,
                    '/',
                    null,
                    true, // secure
                    true  // httpOnly
                );
            } else {
                // Invalid referral code
                Log::warning('Invalid referral code attempt', [
                    'code' => $refCode,
                    'ip' => $request->ip(),
                ]);
            }
        } elseif (!$request->session()->has(config('affiliate.ref_session_key', 'referral_code'))) {
            // Check if we have a referral code in cookie
            $cookieName = config('affiliate.ref_cookie', 'facturino_ref');
            $cookieRefCode = $request->cookie($cookieName);

            if ($cookieRefCode) {
                // Validate and restore to session
                $affiliateLink = AffiliateLink::where('code', $cookieRefCode)
                    ->where('is_active', true)
                    ->first();

                if ($affiliateLink) {
                    $request->session()->put(config('affiliate.ref_session_key', 'referral_code'), $cookieRefCode);
                    $request->session()->put('referral_partner_id', $affiliateLink->partner_id);
                }
            }
        }

        return $next($request);
    }
}

// CLAUDE-CHECKPOINT
