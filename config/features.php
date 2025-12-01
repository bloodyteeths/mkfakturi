<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Feature Flags Configuration
    |--------------------------------------------------------------------------
    |
    | This file manages all Fakturino feature flags using Laravel Pennant.
    | All features default to OFF except partner_mocked_data which defaults
    | to ON for safety until staging validation is complete.
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

    'partner_mocked_data' => [
        'enabled' => env('FEATURE_PARTNER_MOCKED_DATA', true),  // SAFETY: Default ON
        'description' => 'Return mocked data for partner APIs until staging sign-off',
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
        'enabled' => env('FACTURINO_STOCK_V1_ENABLED', false),
        'description' => 'Inventory/stock tracking with warehouses, movements, and WAC valuation',
    ],
];

// CLAUDE-CHECKPOINT
