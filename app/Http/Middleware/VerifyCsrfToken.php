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

        // Payment gateway webhooks (routes/webhooks.php)
        'webhooks/paddle',
        'webhooks/stripe',
        'webhooks/cpay',
        'webhooks/cpay/callback',
        'webhooks/bank/nlb',
        'webhooks/bank/stopanska',

        // Inbound email webhook (routes/webhooks.php)
        'webhooks/email-inbound',

        // Postmark email event webhook (routes/bitrix.php)
        'webhooks/postmark',

        // CPAY payment callback (routes/web.php)
        'payment/cpay/callback',

        // API webhook endpoints (routes/api.php)
        'api/webhooks/paddle',
        'api/webhooks/paddle/subscription',
        'api/webhooks/cpay',
        'api/webhooks/cpay/subscription',
        'api/webhooks/stripe',
        'api/webhooks/bank/nlb',
        'api/webhooks/bank/stopanska',

        // Legacy Bitrix24 CRM integration (routes/bitrix.php)
        'api/bitrix/events',
        'api/bitrix/create-partner',
    ];
    // CLAUDE-CHECKPOINT: CSRF exemptions narrowed from wildcards to explicit routes
}
