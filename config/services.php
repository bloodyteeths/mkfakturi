<?php

return [

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
            'standard' => env('PADDLE_PRICE_STANDARD', 'pri_standard_29eur'),
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

    'psd2_gateway' => [
        'base_url' => env('PSD2_GATEWAY_BASE_URL', 'http://localhost:8080'),
        'redirect_uri' => env('PSD2_REDIRECT_URI'),
        'timeout' => env('PSD2_TIMEOUT', 30),

        // Bank-specific configuration
        'banks' => [
            'nlb' => [
                'client_id' => env('NLB_CLIENT_ID'),
                'client_secret' => env('NLB_CLIENT_SECRET'),
                'environment' => env('NLB_ENVIRONMENT', 'sandbox'),
                'base_url' => env('NLB_BASE_URL', 'https://sandbox-ob-api.nlb.mk'),
                'redirect_uri' => env('NLB_REDIRECT_URI'),
                'scopes' => env('NLB_SCOPES', 'openid'),
                'requires_pkce' => true,
                // Optional mTLS certificate paths
                'cert_path' => env('NLB_CERT_PATH'),
                'key_path' => env('NLB_KEY_PATH'),
                'key_password' => env('NLB_KEY_PASSWORD'),
            ],

            'stopanska' => [
                'client_id' => env('STOPANSKA_CLIENT_ID'),
                'client_secret' => env('STOPANSKA_CLIENT_SECRET'),
                'environment' => env('STOPANSKA_ENVIRONMENT', 'sandbox'),
                'base_url' => env('STOPANSKA_BASE_URL', 'https://sandbox-api.stopanska.mk'),
                'redirect_uri' => env('STOPANSKA_REDIRECT_URI'),
                'scopes' => env('STOPANSKA_SCOPES', 'accounts transactions'),
                'requires_pkce' => false,
            ],

            'komercijalna' => [
                'client_id' => env('KOMERCIJALNA_CLIENT_ID'),
                'client_secret' => env('KOMERCIJALNA_CLIENT_SECRET'),
                'environment' => env('KOMERCIJALNA_ENVIRONMENT', 'sandbox'),
                'base_url' => env('KOMERCIJALNA_BASE_URL', 'https://sandbox-api.kbm.mk'),
                'redirect_uri' => env('KOMERCIJALNA_REDIRECT_URI'),
                'scopes' => env('KOMERCIJALNA_SCOPES', 'accounts transactions'),
                'requires_pkce' => false,
            ],
        ],
    ],

    // CLAUDE-CHECKPOINT

    'invoice2data' => [
        'url' => env('INVOICE2DATA_URL', 'http://invoice2data-service:8000'),
        'timeout' => env('INVOICE2DATA_TIMEOUT', 90),
    ],

];
