<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Facturino Feature Flags
    |--------------------------------------------------------------------------
    |
    | Control which Facturino-specific features are enabled.
    | These features extend the base InvoiceShelf functionality.
    |
    */

    'features' => [
        /*
         * Stock Management Module (v1)
         *
         * Enables inventory/stock tracking with:
         * - Multi-warehouse support
         * - Weighted Average Cost (WAC) valuation
         * - Automatic stock movements from invoices/bills
         * - Stock reports and analytics
         */
        'stock' => true, // Stock module is always enabled

        /*
         * E-Invoice Module
         *
         * Enables Macedonian e-invoice integration
         */
        'einvoice' => env('FACTURINO_EINVOICE_ENABLED', false),

        /*
         * Partner/Affiliate Module
         *
         * Enables partner commission tracking
         */
        'partner' => env('FACTURINO_PARTNER_ENABLED', false),

        /*
         * PSD2 Banking Integration
         *
         * Enables bank feed imports
         */
        'psd2' => env('FACTURINO_PSD2_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Module Versions
    |--------------------------------------------------------------------------
    */

    'versions' => [
        'stock' => '1.0.0',
        'einvoice' => '1.0.0',
        'partner' => '1.0.0',
        'psd2' => '1.0.0',
    ],
];
// CLAUDE-CHECKPOINT
