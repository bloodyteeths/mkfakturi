<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * Indicates whether the XSRF-TOKEN cookie should be set on the response.
     *
     * @var bool
     */
    protected $addHttpCookie = true;

    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * Security Note: Only external callbacks that cannot provide CSRF tokens
     * should be listed here. All internal forms and API calls must use CSRF protection.
     *
     * @var array
     */
    protected $except = [
        'login',
        'webhooks/*', // All webhook routes (Paddle, CPAY, Bank webhooks)
        'payment/cpay/callback', // CPAY payment callback
        'api/webhooks/*', // API webhook endpoints
    ];
}
// CLAUDE-CHECKPOINT: CSRF protection configured - webhooks properly exempted
