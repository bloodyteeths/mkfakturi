<?php

return [

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'sendgrid' => [
        'api_key' => env('SENDGRID_API_KEY'),
    ],

    'stripe' => [
        'model' => \App\Models\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook' => [
            'secret' => env('STRIPE_WEBHOOK_SECRET'),
            'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
        ],

        // Subscription price IDs (MKD) - Monthly and Yearly
        'prices' => [
            'starter' => [
                'monthly' => env('STRIPE_PRICE_STARTER_MKD_MONTHLY'),
                'yearly' => env('STRIPE_PRICE_STARTER_MKD_YEARLY'),
            ],
            'standard' => [
                'monthly' => env('STRIPE_PRICE_STANDARD_MKD_MONTHLY'),
                'yearly' => env('STRIPE_PRICE_STANDARD_MKD_YEARLY'),
            ],
            'business' => [
                'monthly' => env('STRIPE_PRICE_BUSINESS_MKD_MONTHLY'),
                'yearly' => env('STRIPE_PRICE_BUSINESS_MKD_YEARLY'),
            ],
            'max' => [
                'monthly' => env('STRIPE_PRICE_MAX_MKD_MONTHLY'),
                'yearly' => env('STRIPE_PRICE_MAX_MKD_YEARLY'),
            ],
        ],

        // Subscription price IDs (EUR) - for SEPA bank transfer payments
        'prices_eur' => [
            'starter' => [
                'monthly' => env('STRIPE_PRICE_STARTER_EUR_MONTHLY'),
                'yearly' => env('STRIPE_PRICE_STARTER_EUR_YEARLY'),
            ],
            'standard' => [
                'monthly' => env('STRIPE_PRICE_STANDARD_EUR_MONTHLY'),
                'yearly' => env('STRIPE_PRICE_STANDARD_EUR_YEARLY'),
            ],
            'business' => [
                'monthly' => env('STRIPE_PRICE_BUSINESS_EUR_MONTHLY'),
                'yearly' => env('STRIPE_PRICE_BUSINESS_EUR_YEARLY'),
            ],
            'max' => [
                'monthly' => env('STRIPE_PRICE_MAX_EUR_MONTHLY'),
                'yearly' => env('STRIPE_PRICE_MAX_EUR_YEARLY'),
            ],
        ],

        'currency' => 'mkd',

        // Stripe Connect for partner payouts (Cross-border to Macedonia)
        'connect' => [
            'client_id' => env('STRIPE_CONNECT_CLIENT_ID'),
            // MKD is the required currency for MK country
            'payout_currency' => 'mkd',
        ],
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URL'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URL'),
    ],

    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('GITHUB_REDIRECT_URL'),
    ],

    // eID / OneID — Macedonian national identity OIDC provider (P13-03)
    'oneid' => [
        'client_id' => env('ONEID_CLIENT_ID'),
        'client_secret' => env('ONEID_CLIENT_SECRET'),
        'redirect' => env('ONEID_REDIRECT_URL', env('APP_URL') . '/auth/oneid/callback'),
        'authorize_url' => env('ONEID_AUTHORIZE_URL', 'https://eid.mk/connect/authorize'),
        'token_url' => env('ONEID_TOKEN_URL', 'https://eid.mk/connect/token'),
        'userinfo_url' => env('ONEID_USERINFO_URL', 'https://eid.mk/connect/userinfo'),
    ], // CLAUDE-CHECKPOINT

    'cron_job' => [
        'auth_token' => env('CRON_JOB_AUTH_TOKEN', 0),
    ],

    'ses' => [
        'key' => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => env('SES_REGION', 'us-east-1'),
    ],

    'minimax' => [
        'base_url' => env('MINIMAX_BASE_URL', 'https://api.minimax.mk/v1'),
        'sandbox_url' => env('MINIMAX_SANDBOX_URL', 'https://sandbox-api.minimax.mk/v1'),
        'api_key' => env('MINIMAX_API_KEY'),
        'timeout' => env('MINIMAX_TIMEOUT', 30),
        'rate_limit' => env('MINIMAX_RATE_LIMIT', 50),
        'environment' => env('MINIMAX_ENVIRONMENT', 'sandbox'), // sandbox or production
    ],

    'paddle' => [
        'vendor_id' => env('PADDLE_VENDOR_ID'),
        'api_key' => env('PADDLE_API_KEY'),
        'webhook_secret' => env('PADDLE_WEBHOOK_SECRET'),
        'environment' => env('PADDLE_ENVIRONMENT', 'sandbox'), // sandbox or production
        'price_id' => env('PADDLE_PRICE_ID'), // One-time payment price ID

        // Subscription price IDs for company tiers (B-31 series)
        'prices' => [
            'starter' => env('PADDLE_PRICE_STARTER', 'pri_starter_12eur'),
            'standard' => env('PADDLE_PRICE_STANDARD', 'pri_standard_39eur'),
            'business' => env('PADDLE_PRICE_BUSINESS', 'pri_business_59eur'),
            'max' => env('PADDLE_PRICE_MAX', 'pri_max_149eur'),
            'partner_plus' => env('PADDLE_PRICE_PARTNER_PLUS', 'pri_partner_plus_29eur'),
        ],
    ], // CLAUDE-CHECKPOINT: Added subscription price IDs

    'mcp' => [
        'server_url' => env('MCP_SERVER_URL', 'http://localhost:3100'),
        'token' => env('MCP_SERVER_TOKEN'),
        'timeout' => env('MCP_TIMEOUT', 30),
    ],

    // PSD2 banking config moved to config/mk.php (stopanska, nlb, komercijalna sections)


    'invoice_parser_driver' => env('INVOICE_PARSER_DRIVER', 'invoice2data'),

    'invoice2data' => [
        'url' => env('INVOICE2DATA_URL', 'http://invoice2data-service:8000'),
        'timeout' => env('INVOICE2DATA_TIMEOUT', 90),
    ],

    'azure_document_intelligence' => [
        'endpoint' => env('AZURE_DOCUMENT_INTELLIGENCE_ENDPOINT'),
        'key' => env('AZURE_DOCUMENT_INTELLIGENCE_KEY'),
        'api_version' => env('AZURE_DOCUMENT_INTELLIGENCE_API_VERSION', '2024-11-30'),
        'timeout' => (int) env('AZURE_DOCUMENT_INTELLIGENCE_TIMEOUT', 120),
    ], // CLAUDE-CHECKPOINT

    'bitrix' => [
        'webhook_url' => env('BITRIX_WEBHOOK_URL'),
        'shared_secret' => env('BITRIX_SHARED_SECRET'),
    ],

    'clawd' => [
        'webhook_url' => env('CLAWD_WEBHOOK_URL'),
        'monitor_token' => env('CLAWD_MONITOR_TOKEN'),
    ],

];
