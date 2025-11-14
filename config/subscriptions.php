<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Subscription Tier Configuration
    |--------------------------------------------------------------------------
    |
    | This file defines feature limits and access controls for each
    | subscription tier in Facturino. Companies must upgrade to access
    | premium features.
    |
    | Tiers:
    | - Free: €0/month - Basic invoicing
    | - Starter: €12/month - More invoices + recurring invoices
    | - Standard: €29/month - E-Faktura + QES signing + more users
    | - Business: €59/month - Bank feeds + auto-reconciliation
    | - Max: €149/month - Unlimited everything
    |
    */

    'tiers' => [
        'free' => [
            'name' => 'Free',
            'price_monthly' => 0.00,
            'invoice_limit' => 5,
            'users' => 1,
            'features' => [
                'basic_invoicing' => true,
                'customers' => true,
                'items' => true,
                'pdf_export' => true,
                'csv_import' => true, // Free alternative to bank feeds

                // Locked features
                'recurring_invoices' => false,
                'efaktura_sending' => false,
                'qes_signing' => false,
                'bank_connections' => false,
                'auto_reconciliation' => false,
                'multi_currency' => false,
                'custom_fields' => false,
                'api_access' => false,
            ],
        ],

        'starter' => [
            'name' => 'Starter',
            'price_monthly' => 12.00,
            'invoice_limit' => 50,
            'users' => 1,
            'features' => [
                'basic_invoicing' => true,
                'customers' => true,
                'items' => true,
                'pdf_export' => true,
                'csv_import' => true,
                'recurring_invoices' => true,
                'estimates' => true,

                // Locked features
                'efaktura_sending' => false,
                'qes_signing' => false,
                'bank_connections' => false,
                'auto_reconciliation' => false,
                'multi_currency' => false,
                'custom_fields' => false,
                'api_access' => false,
            ],
        ],

        'standard' => [
            'name' => 'Standard',
            'price_monthly' => 29.00,
            'invoice_limit' => 200,
            'users' => 3,
            'features' => [
                'basic_invoicing' => true,
                'customers' => true,
                'items' => true,
                'pdf_export' => true,
                'csv_import' => true,
                'recurring_invoices' => true,
                'estimates' => true,
                'efaktura_sending' => true, // Standard+ only
                'qes_signing' => true, // Standard+ only
                'expenses' => true,
                'reports' => true,

                // Locked features
                'bank_connections' => false,
                'auto_reconciliation' => false,
                'multi_currency' => false,
                'custom_fields' => false,
                'api_access' => false,
            ],
        ],

        'business' => [
            'name' => 'Business',
            'price_monthly' => 59.00,
            'invoice_limit' => 1000,
            'users' => 5,
            'features' => [
                'basic_invoicing' => true,
                'customers' => true,
                'items' => true,
                'pdf_export' => true,
                'csv_import' => true,
                'recurring_invoices' => true,
                'estimates' => true,
                'efaktura_sending' => true,
                'qes_signing' => true,
                'expenses' => true,
                'reports' => true,
                'bank_connections' => true, // Business+ only
                'auto_reconciliation' => true, // Business+ only
                'multi_currency' => true,
                'custom_fields' => true,

                // Locked features
                'api_access' => false,
            ],
        ],

        'max' => [
            'name' => 'Max',
            'price_monthly' => 149.00,
            'invoice_limit' => null, // Unlimited
            'users' => null, // Unlimited
            'features' => [
                'basic_invoicing' => true,
                'customers' => true,
                'items' => true,
                'pdf_export' => true,
                'csv_import' => true,
                'recurring_invoices' => true,
                'estimates' => true,
                'efaktura_sending' => true,
                'qes_signing' => true,
                'expenses' => true,
                'reports' => true,
                'bank_connections' => true,
                'auto_reconciliation' => true,
                'multi_currency' => true,
                'custom_fields' => true,
                'api_access' => true, // Max only
                'priority_support' => true,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Plan Hierarchy
    |--------------------------------------------------------------------------
    |
    | Defines the order of plans for upgrade checks.
    | Higher number = more premium tier.
    |
    */
    'plan_hierarchy' => [
        'free' => 0,
        'starter' => 1,
        'standard' => 2,
        'business' => 3,
        'max' => 4,
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Requirements
    |--------------------------------------------------------------------------
    |
    | Maps features to minimum required plan.
    | Used by middleware and authorization checks.
    |
    */
    'feature_requirements' => [
        // Free tier features
        'basic_invoicing' => 'free',
        'customers' => 'free',
        'items' => 'free',
        'pdf_export' => 'free',
        'csv_import' => 'free',

        // Starter tier features
        'recurring_invoices' => 'starter',
        'estimates' => 'starter',

        // Standard tier features (e-Faktura)
        'efaktura_sending' => 'standard',
        'qes_signing' => 'standard',
        'expenses' => 'standard',
        'reports' => 'standard',

        // Business tier features (bank feeds)
        'bank_connections' => 'business',
        'auto_reconciliation' => 'business',
        'multi_currency' => 'business',
        'custom_fields' => 'business',

        // Max tier features
        'api_access' => 'max',
        'priority_support' => 'max',
    ],

    /*
    |--------------------------------------------------------------------------
    | Trial Configuration
    |--------------------------------------------------------------------------
    |
    | New companies get 14 days of Standard tier trial.
    | After trial ends, they downgrade to Free unless they subscribe.
    |
    */
    'trial' => [
        'enabled' => true,
        'duration_days' => 14,
        'plan' => 'standard', // Give Standard features during trial
        'email_reminders' => [
            7, // 7 days before expiry
            1, // 1 day before expiry
            0, // On expiry day
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Invoice Reset Configuration
    |--------------------------------------------------------------------------
    |
    | Invoice limits reset monthly on the 1st of each month.
    | This job runs daily and resets counters if needed.
    |
    */
    'invoice_reset' => [
        'enabled' => true,
        'reset_day' => 1, // 1st of each month
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Invoice count caching to improve performance.
    | TTL in seconds (5 minutes default).
    |
    */
    'cache' => [
        'enabled' => env('SUBSCRIPTION_CACHE_ENABLED', true),
        'ttl' => env('SUBSCRIPTION_CACHE_TTL', 300), // 5 minutes
        'prefix' => 'subscription:',
    ],

    /*
    |--------------------------------------------------------------------------
    | Paddle Price IDs
    |--------------------------------------------------------------------------
    |
    | Map Facturino tiers to Paddle price IDs for checkout.
    | These are synced from config/services.php but can be overridden here.
    |
    */
    'paddle_prices' => [
        'starter' => config('services.paddle.prices.starter'),
        'standard' => config('services.paddle.prices.standard'),
        'business' => config('services.paddle.prices.business'),
        'max' => config('services.paddle.prices.max'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Upgrade CTAs
    |--------------------------------------------------------------------------
    |
    | Messages shown to users when they hit limits or try to access
    | gated features.
    |
    */
    'upgrade_messages' => [
        'invoice_limit' => [
            'free' => 'You\'ve reached your invoice limit (5/month). Upgrade to Starter for 50 invoices per month.',
            'starter' => 'You\'ve reached your invoice limit (50/month). Upgrade to Standard for 200 invoices per month.',
            'standard' => 'You\'ve reached your invoice limit (200/month). Upgrade to Business for 1,000 invoices per month.',
            'business' => 'You\'ve reached your invoice limit (1,000/month). Upgrade to Max for unlimited invoices.',
        ],
        'efaktura' => 'E-Faktura sending requires a Standard plan or higher. Upgrade now to send digital invoices to the government.',
        'qes_signing' => 'QES digital signing requires a Standard plan or higher. Upgrade now to digitally sign invoices.',
        'bank_connections' => 'Bank connections require a Business plan or higher. Upgrade now to connect your bank accounts via PSD2.',
        'auto_reconciliation' => 'Automatic reconciliation requires a Business plan or higher. Upgrade now to automatically match transactions.',
        'user_limit' => [
            'free' => 'You\'ve reached your user limit (1 user). Upgrade to Standard for 3 users.',
            'starter' => 'You\'ve reached your user limit (1 user). Upgrade to Standard for 3 users.',
            'standard' => 'You\'ve reached your user limit (3 users). Upgrade to Business for 5 users.',
            'business' => 'You\'ve reached your user limit (5 users). Upgrade to Max for unlimited users.',
        ],
    ],
];
// CLAUDE-CHECKPOINT
