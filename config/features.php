<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Feature Flags Configuration
    |--------------------------------------------------------------------------
    |
    | This file manages all Fakturino feature flags using Laravel Pennant.
    | All features default to OFF for safety until enabled in production.
    |
    */

    'accounting_backbone' => [
        'enabled' => env('FEATURE_ACCOUNTING_BACKBONE', false),
        'description' => 'eloquent-ifrs double-entry accounting integration',
    ],

    'migration_wizard' => [
        'enabled' => env('FEATURE_MIGRATION_WIZARD', false),
        'description' => 'Laravel Excel CSV/XLSX import wizard with presets',
    ],

    'psd2_banking' => [
        'enabled' => env('FEATURE_PSD2_BANKING', false),
        'description' => 'PSD2 OAuth banking integration + MT940 fallback',
    ],

    'partner_portal' => [
        'enabled' => env('FEATURE_PARTNER_PORTAL', false),
        'description' => 'Partner referral portal with commission tracking',
    ],

    'advanced_payments' => [
        'enabled' => env('FEATURE_ADVANCED_PAYMENTS', false),
        'description' => 'Paddle and CPAY payment gateway integrations',
    ],

    'redis_queues' => [
        'enabled' => env('FEATURE_REDIS_QUEUES', false),
        'description' => 'Use Redis-backed queues instead of database/sync (requires Redis service).',
    ],

    'mcp_ai_tools' => [
        'enabled' => env('FEATURE_MCP_AI_TOOLS', false),
        'description' => 'MCP AI tools server (UBL validation, tax explanations)',
    ],

    'monitoring' => [
        'enabled' => env('FEATURE_MONITORING', false),
        'description' => 'Prometheus metrics + Telescope debugging interface',
    ],

    'stock' => [
        'enabled' => true, // Stock module is always enabled
        'description' => 'Inventory/stock tracking with warehouses, movements, and WAC valuation',
    ],

    'payroll' => [
        'enabled' => env('FEATURE_PAYROLL', false),
        'description' => 'MK payroll management with tax compliance, GL integration, and UJP e-filing',
        'minimum_tier' => 'business', // Requires Business tier or higher
    ],
];

// LLM-CHECKPOINT
