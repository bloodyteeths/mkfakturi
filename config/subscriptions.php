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
            'invoice_limit' => 3,
            'users' => 1,
            'features' => [
                'basic_invoicing' => true,
                'customers' => true,
                'items' => true,
                'pdf_export' => true,
                'csv_import' => true,

                // Preview features with tight limits (see the feature, upgrade to use it)
                'expenses' => true,           // Limited to 2/month
                'custom_fields' => true,      // Limited to 1 field
                'reports' => true,            // Basic reports only
                'recurring_invoices' => false, // Not available on free
                'estimates' => true,          // Limited to 1/month

                // Locked features (require paid plan)
                'efaktura_sending' => false,  // Requires Starter+
                'qes_signing' => false,
                'bank_connections' => false,
                'auto_reconciliation' => false,
                'multi_currency' => false,
                'api_access' => false,
                'ai_suggestions' => 'preview', // Limited AI preview (2/month)
            ],
            // Usage limits — see the feature exists, upgrade to actually use it
            'limits' => [
                'expenses_per_month' => 2,
                'custom_fields' => 1,
                'recurring_invoices_active' => 0,
                'estimates_per_month' => 1,
                'ai_queries_per_month' => 2,
                'payroll_employees' => 0,     // Not available on free
                'bills_per_month' => 1,
                'suppliers_total' => 3,
                'credit_notes_per_month' => 0,
                'proformas_per_month' => 1,
                'projects_total' => 0,
                'warehouses_total' => 0,
                'deadlines_custom' => 1,
                'client_documents_per_month' => 2,
                'efaktura_per_month' => 0,    // Not available on free
            ],
        ],

        'starter' => [
            'name' => 'Starter',
            'price_monthly' => 12.00,
            'invoice_limit' => 30,
            'users' => 1,
            'features' => [
                'basic_invoicing' => true,
                'customers' => true,
                'items' => true,
                'pdf_export' => true,
                'csv_import' => true,
                'recurring_invoices' => true,
                'estimates' => true,
                'expenses' => true,
                'custom_fields' => true,
                'reports' => true,
                'ai_suggestions' => 'basic',  // Basic AI suggestions
                'efaktura_sending' => true,   // Limited: 5/month (key differentiator)

                // Locked features
                'qes_signing' => false,
                'bank_connections' => false,
                'auto_reconciliation' => false,
                'multi_currency' => false,
                'api_access' => false,
            ],
            'limits' => [
                'expenses_per_month' => 20,
                'custom_fields' => 3,
                'recurring_invoices_active' => 3,
                'estimates_per_month' => 10,
                'ai_queries_per_month' => 5,
                'payroll_employees' => 0,     // Payroll not available on starter
                'bills_per_month' => 10,
                'suppliers_total' => 10,
                'credit_notes_per_month' => 5,
                'proformas_per_month' => 5,
                'projects_total' => 3,
                'warehouses_total' => 1,
                'deadlines_custom' => 5,
                'client_documents_per_month' => 10,
                'efaktura_per_month' => 5,    // E-Faktura: 5/month (key Starter differentiator)
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
                'efaktura_sending' => true,
                'qes_signing' => true,
                'expenses' => true,
                'reports' => true,
                'custom_fields' => true,
                'bank_connections' => true,   // PSD2 available in Standard (matches landing page)
                'auto_reconciliation' => true,
                'ai_suggestions' => 'standard', // Standard AI

                // Locked features
                'multi_currency' => false,
                'api_access' => false,
            ],
            'limits' => [
                'expenses_per_month' => null,
                'custom_fields' => 15,
                'recurring_invoices_active' => 20,
                'estimates_per_month' => null,
                'bank_accounts' => 2,
                'ai_queries_per_month' => 25,
                'payroll_employees' => 0,     // Payroll not available on standard
                'bills_per_month' => 100,
                'suppliers_total' => 100,
                'credit_notes_per_month' => 50,
                'proformas_per_month' => 50,
                'projects_total' => 20,
                'warehouses_total' => 5,
                'deadlines_custom' => 50,
                'client_documents_per_month' => 100,
                'efaktura_per_month' => null,  // Unlimited e-faktura on Standard+
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
                'bank_connections' => true,
                'auto_reconciliation' => true,
                'multi_currency' => true,
                'custom_fields' => true,
                'api_access' => true,         // API available in Business (matches landing page)
                'ai_suggestions' => 'advanced', // Advanced AI
            ],
            'limits' => [
                'expenses_per_month' => null,
                'custom_fields' => null,
                'recurring_invoices_active' => null,
                'estimates_per_month' => null,
                'bank_accounts' => 5,
                'api_requests_per_day' => 1000,
                'ai_queries_per_month' => 50,
                'payroll_employees' => 50,
                'bills_per_month' => 500,
                'suppliers_total' => 500,
                'credit_notes_per_month' => 200,
                'proformas_per_month' => 200,
                'projects_total' => 100,
                'warehouses_total' => 20,
                'deadlines_custom' => 200,
                'client_documents_per_month' => 500,
                'efaktura_per_month' => null,
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
                'api_access' => true,
                'ai_suggestions' => 'advanced',
                'priority_support' => true,
                'multi_location' => true,
                'ifrs_reports' => true,
            ],
            'limits' => [
                // All unlimited except AI (token costs)
                'expenses_per_month' => null,
                'custom_fields' => null,
                'recurring_invoices_active' => null,
                'estimates_per_month' => null,
                'ai_queries_per_month' => 100,
                'bank_accounts' => null,
                'api_requests_per_day' => null,
                'payroll_employees' => null,
                'bills_per_month' => null,
                'suppliers_total' => null,
                'credit_notes_per_month' => null,
                'proformas_per_month' => null,
                'projects_total' => null,
                'warehouses_total' => null,
                'deadlines_custom' => null,
                'client_documents_per_month' => null,
                'efaktura_per_month' => null,
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
    | Note: Features marked 'free' may still have usage limits defined
    | in the 'limits' array of each tier. The middleware should check
    | both feature access AND usage limits.
    |
    */
    'feature_requirements' => [
        // Free tier features (available to all, may have limits)
        'basic_invoicing' => 'free',
        'customers' => 'free',
        'items' => 'free',
        'pdf_export' => 'free',
        'csv_import' => 'free',
        'expenses' => 'free',           // Available but limited on free
        'custom_fields' => 'free',      // Available but limited on free
        'reports' => 'free',            // Basic reports on free
        'recurring_invoices' => 'free', // Limited to 1 on free
        'estimates' => 'free',          // Limited on free

        // Standard tier features (PSD2 + QES)
        'qes_signing' => 'standard',
        'bank_connections' => 'standard',     // PSD2 in Standard (matches landing page)
        'auto_reconciliation' => 'standard',

        // Business tier features
        'multi_currency' => 'business',
        'api_access' => 'business',           // API in Business (matches landing page)

        // Max tier features
        'priority_support' => 'max',
        'multi_location' => 'max',
        'ifrs_reports' => 'max',

        // AI tiers (special handling - level based with usage limits)
        'ai_suggestions' => 'free',           // Preview AI for all (limited on free)
        'ai_advanced' => 'business',          // Advanced AI at Business+

        // E-faktura requires Starter+ (key upgrade driver)
        'efaktura_sending' => 'starter',      // 5/month on Starter, unlimited on Standard+
        'payroll' => 'business',              // Payroll only on Business+ (50 employees)
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
            'free' => 'You\'ve reached your invoice limit (3/month). Upgrade to Starter for 30 invoices per month.',
            'starter' => 'You\'ve reached your invoice limit (30/month). Upgrade to Standard for 200 invoices per month.',
            'standard' => 'You\'ve reached your invoice limit (200/month). Upgrade to Business for 1,000 invoices per month.',
            'business' => 'You\'ve reached your invoice limit (1,000/month). Upgrade to Max for unlimited invoices.',
        ],
        'expenses' => [
            'free' => 'You\'ve reached your expense limit (2/month). Upgrade to Starter for 20 expenses per month.',
            'starter' => 'You\'ve reached your expense limit (20/month). Upgrade to Standard for unlimited expenses.',
            'standard' => 'Unlimited expenses available.',
            'business' => 'Unlimited expenses available.',
        ],
        'estimates' => [
            'free' => 'You\'ve reached your estimate limit (1/month). Upgrade to Starter for 10 estimates per month.',
            'starter' => 'You\'ve reached your estimate limit (10/month). Upgrade to Standard for unlimited estimates.',
            'standard' => 'Unlimited estimates available.',
            'business' => 'Unlimited estimates available.',
        ],
        'custom_fields' => [
            'free' => 'You\'ve reached your custom field limit (1 field). Upgrade to Starter for 3 custom fields.',
            'starter' => 'You\'ve reached your custom field limit (3 fields). Upgrade to Standard for 15 custom fields.',
            'standard' => 'You\'ve reached your custom field limit (15 fields). Upgrade to Business for unlimited custom fields.',
            'business' => 'Unlimited custom fields available.',
        ],
        'recurring_invoices' => [
            'free' => 'Recurring invoices are not available on the Free plan. Upgrade to Starter for 3 active recurring invoices.',
            'starter' => 'You\'ve reached your recurring invoice limit (3 active). Upgrade to Standard for 20 active recurring invoices.',
            'standard' => 'You\'ve reached your recurring invoice limit (20 active). Upgrade to Business for unlimited recurring invoices.',
            'business' => 'Unlimited recurring invoices available.',
        ],
        'efaktura' => 'E-Faktura sending requires a Starter plan or higher. Upgrade now to send digital invoices.',
        'qes_signing' => 'QES digital signing requires a Standard plan or higher. Upgrade now to digitally sign invoices.',
        'bank_connections' => 'Bank connections require a Business plan or higher. Upgrade now to connect your bank accounts via PSD2.',
        'auto_reconciliation' => 'Automatic reconciliation requires a Business plan or higher. Upgrade now to automatically match transactions.',
        'user_limit' => [
            'free' => 'Free plan is limited to 1 user. Upgrade to Standard for 3 users.',
            'starter' => 'Starter plan is limited to 1 user. Upgrade to Standard for 3 users.',
            'standard' => 'You\'ve reached your user limit (3 users). Upgrade to Business for 5 users.',
            'business' => 'You\'ve reached your user limit (5 users). Upgrade to Max for unlimited users.',
        ],
        'ai_suggestions' => [
            'free' => 'You\'ve reached your AI query limit (2/month). Upgrade to Starter for 5 AI queries per month.',
            'starter' => 'You\'ve reached your AI query limit (5/month). Upgrade to Standard for 25 AI queries per month.',
            'standard' => 'You\'ve reached your AI query limit (25/month). Upgrade to Business for 50 AI queries per month.',
            'business' => 'You\'ve reached your AI query limit (50/month). Upgrade to Max for 100 AI queries per month.',
            'max' => 'You\'ve reached your AI query limit (100/month). Contact support for higher limits.',
        ],
        'payroll' => 'Payroll management requires a Business plan. Upgrade to Business for payroll with up to 50 employees.',
        'payroll_employees' => [
            'free' => 'Payroll is not available on the Free plan. Upgrade to Business for payroll management with 50 employees.',
            'starter' => 'Payroll is not available on the Starter plan. Upgrade to Business for payroll management with 50 employees.',
            'standard' => 'Payroll is not available on the Standard plan. Upgrade to Business for payroll management with 50 employees.',
            'business' => 'You\'ve reached your employee limit (50 employees). Upgrade to Max for unlimited employees.',
        ],
        'bills_per_month' => [
            'free' => 'You\'ve reached your bill limit (1/month). Upgrade to Starter for 10 bills per month.',
            'starter' => 'You\'ve reached your bill limit (10/month). Upgrade to Standard for 100 bills per month.',
            'standard' => 'You\'ve reached your bill limit (100/month). Upgrade to Business for 500 bills per month.',
            'business' => 'You\'ve reached your bill limit (500/month). Upgrade to Max for unlimited bills.',
        ],
        'suppliers_total' => [
            'free' => 'You\'ve reached your supplier limit (3 suppliers). Upgrade to Starter for 10 suppliers.',
            'starter' => 'You\'ve reached your supplier limit (10 suppliers). Upgrade to Standard for 100 suppliers.',
            'standard' => 'You\'ve reached your supplier limit (100 suppliers). Upgrade to Business for 500 suppliers.',
            'business' => 'You\'ve reached your supplier limit (500 suppliers). Upgrade to Max for unlimited suppliers.',
        ],
        'credit_notes_per_month' => [
            'free' => 'Credit notes are not available on the Free plan. Upgrade to Starter for 5 credit notes per month.',
            'starter' => 'You\'ve reached your credit note limit (5/month). Upgrade to Standard for 50 per month.',
            'standard' => 'You\'ve reached your credit note limit (50/month). Upgrade to Business for 200 per month.',
            'business' => 'You\'ve reached your credit note limit (200/month). Upgrade to Max for unlimited credit notes.',
        ],
        'proformas_per_month' => [
            'free' => 'You\'ve reached your proforma limit (1/month). Upgrade to Starter for 5 proformas per month.',
            'starter' => 'You\'ve reached your proforma limit (5/month). Upgrade to Standard for 50 per month.',
            'standard' => 'You\'ve reached your proforma limit (50/month). Upgrade to Business for 200 per month.',
            'business' => 'You\'ve reached your proforma limit (200/month). Upgrade to Max for unlimited proformas.',
        ],
        'projects_total' => [
            'free' => 'Projects are not available on the Free plan. Upgrade to Starter for 3 projects.',
            'starter' => 'You\'ve reached your project limit (3 projects). Upgrade to Standard for 20 projects.',
            'standard' => 'You\'ve reached your project limit (20 projects). Upgrade to Business for 100 projects.',
            'business' => 'You\'ve reached your project limit (100 projects). Upgrade to Max for unlimited projects.',
        ],
        'warehouses_total' => [
            'free' => 'Warehouses are not available on the Free plan. Upgrade to Starter for 1 warehouse.',
            'starter' => 'You\'ve reached your warehouse limit (1 warehouse). Upgrade to Standard for 5 warehouses.',
            'standard' => 'You\'ve reached your warehouse limit (5 warehouses). Upgrade to Business for 20 warehouses.',
            'business' => 'You\'ve reached your warehouse limit (20 warehouses). Upgrade to Max for unlimited warehouses.',
        ],
        'deadlines_custom' => [
            'free' => 'You\'ve reached your custom deadline limit (1 deadline). Upgrade to Starter for 5 custom deadlines.',
            'starter' => 'You\'ve reached your custom deadline limit (5 deadlines). Upgrade to Standard for 50.',
            'standard' => 'You\'ve reached your custom deadline limit (50 deadlines). Upgrade to Business for 200.',
            'business' => 'You\'ve reached your custom deadline limit (200 deadlines). Upgrade to Max for unlimited.',
        ],
        'client_documents_per_month' => [
            'free' => 'You\'ve reached your document upload limit (2/month). Upgrade to Starter for 10 per month.',
            'starter' => 'You\'ve reached your document upload limit (10/month). Upgrade to Standard for 100 per month.',
            'standard' => 'You\'ve reached your document upload limit (100/month). Upgrade to Business for 500 per month.',
            'business' => 'You\'ve reached your document upload limit (500/month). Upgrade to Max for unlimited.',
        ],
        'efaktura_per_month' => [
            'free' => 'E-Faktura is not available on the Free plan. Upgrade to Starter for 5 e-Faktura per month.',
            'starter' => 'You\'ve reached your e-Faktura limit (5/month). Upgrade to Standard for unlimited e-Faktura sending.',
        ],
    ],
];
// LLM-CHECKPOINT
