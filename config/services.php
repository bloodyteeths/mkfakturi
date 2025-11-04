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
    ],

    'mcp' => [
        'server_url' => env('MCP_SERVER_URL', 'http://localhost:3100'),
        'token' => env('MCP_SERVER_TOKEN'),
        'timeout' => env('MCP_TIMEOUT', 30),
    ],

    // CLAUDE-CHECKPOINT

];
