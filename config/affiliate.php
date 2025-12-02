<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Commission Rates
    |--------------------------------------------------------------------------
    |
    | Define the commission structure for the multi-level affiliate system.
    | Accountants earn commission from company subscriptions.
    |
    */

    // Direct partner commission rate (standard accountant)
    // Partner who brings companies always gets 20%
    'direct_rate' => env('AFFILIATE_DIRECT_RATE', 0.20), // 20%

    // Direct partner commission rate for Partner Plus members
    'direct_rate_plus' => env('AFFILIATE_DIRECT_RATE_PLUS', 0.22), // 22%

    // Upline commission rate (for the partner who referred the direct partner)
    // Upline partner also gets 20% of what their downline earns
    'upline_rate' => env('AFFILIATE_UPLINE_RATE', 0.20), // 20% of subscription

    // Sales rep commission rate (for Facturino employee/agency who brought the accountant)
    'sales_rep_rate' => env('AFFILIATE_SALES_REP_RATE', 0.05), // 5%

    // When multi-level is enabled, both direct and upline get 20%
    // Example: €100 subscription -> Direct partner: €20, Upline partner: €20
    'direct_rate_multi_level' => env('AFFILIATE_DIRECT_RATE_ML', 0.20), // 20% (direct keeps full rate)

    /*
    |--------------------------------------------------------------------------
    | Bounties
    |--------------------------------------------------------------------------
    |
    | One-time payments for achieving specific milestones.
    |
    */

    // Partner activation bounty (€300 when partner becomes eligible)
    'partner_bounty' => env('AFFILIATE_PARTNER_BOUNTY', 300.00),

    // Company signup bounty (€50 per company referred)
    'company_bounty' => env('AFFILIATE_COMPANY_BOUNTY', 50.00),

    /*
    |--------------------------------------------------------------------------
    | Payout Settings
    |--------------------------------------------------------------------------
    |
    | Configure payout thresholds and schedules.
    |
    */

    // Minimum payout amount threshold (€100)
    'payout_min' => env('AFFILIATE_PAYOUT_MIN', 100.00),

    // Day of month to process payouts (5th of each month)
    'payout_day' => env('AFFILIATE_PAYOUT_DAY', 5),

    // Payout processing time (24-hour format)
    'payout_time' => env('AFFILIATE_PAYOUT_TIME', '02:00'),

    /*
    |--------------------------------------------------------------------------
    | Clawback Settings
    |--------------------------------------------------------------------------
    |
    | Handle commission reversals for refunds and cancellations.
    |
    */

    // Days after which refunds trigger clawback
    'clawback_days' => env('AFFILIATE_CLAWBACK_DAYS', 30),

    // Whether to auto-clawback on subscription cancellation
    'auto_clawback_cancel' => env('AFFILIATE_AUTO_CLAWBACK_CANCEL', false),

    // Whether to auto-clawback on refunds
    'auto_clawback_refund' => env('AFFILIATE_AUTO_CLAWBACK_REFUND', true),

    /*
    |--------------------------------------------------------------------------
    | Partner Eligibility
    |--------------------------------------------------------------------------
    |
    | Requirements for partners to receive activation bounty.
    |
    */

    // Minimum number of active companies for bounty eligibility
    'bounty_min_companies' => env('AFFILIATE_BOUNTY_MIN_COMPANIES', 3),

    // Minimum days since signup for bounty eligibility (alternative to min companies)
    'bounty_min_days' => env('AFFILIATE_BOUNTY_MIN_DAYS', 30),

    // KYC verification required for bounty
    'bounty_requires_kyc' => env('AFFILIATE_BOUNTY_REQUIRES_KYC', true),

    /*
    |--------------------------------------------------------------------------
    | Referral Link Settings
    |--------------------------------------------------------------------------
    |
    | Configure referral tracking behavior.
    |
    */

    // Query parameter name for referral codes
    'ref_param' => env('AFFILIATE_REF_PARAM', 'ref'),

    // Cookie name for storing referral code
    'ref_cookie' => env('AFFILIATE_REF_COOKIE', 'facturino_ref'),

    // Cookie lifetime in minutes (30 days)
    'ref_cookie_lifetime' => env('AFFILIATE_REF_COOKIE_LIFETIME', 43200),

    // Session key for referral code
    'ref_session_key' => env('AFFILIATE_REF_SESSION_KEY', 'referral_code'),

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | Default currency for commission calculations.
    |
    */

    'currency' => env('AFFILIATE_CURRENCY', 'EUR'),

    /*
    |--------------------------------------------------------------------------
    | Partner Plus Tier
    |--------------------------------------------------------------------------
    |
    | Criteria for Partner Plus status (higher commission rate).
    |
    */

    // Minimum active companies for Partner Plus status
    'plus_tier_min_companies' => env('AFFILIATE_PLUS_MIN_COMPANIES', 10),

    // Minimum monthly recurring revenue for Partner Plus
    'plus_tier_min_mrr' => env('AFFILIATE_PLUS_MIN_MRR', 500.00),

    // Months of history required for Partner Plus
    'plus_tier_min_months' => env('AFFILIATE_PLUS_MIN_MONTHS', 3),

    /*
    |--------------------------------------------------------------------------
    | Event Types
    |--------------------------------------------------------------------------
    |
    | Define the types of events that generate commissions.
    |
    */

    'event_types' => [
        'recurring_commission' => 'Recurring Commission',
        'company_bounty' => 'Company Signup Bounty',
        'partner_bounty' => 'Partner Activation Bounty',
        'clawback' => 'Commission Clawback',
        'adjustment' => 'Manual Adjustment',
    ],

    /*
    |--------------------------------------------------------------------------
    | Payout Statuses
    |--------------------------------------------------------------------------
    |
    | Valid statuses for payout records.
    |
    */

    'payout_statuses' => [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'cancelled' => 'Cancelled',
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Methods
    |--------------------------------------------------------------------------
    |
    | Available payment methods for payouts.
    |
    */

    'payment_methods' => [
        'bank_transfer' => 'Bank Transfer',
        'paypal' => 'PayPal',
        'stripe' => 'Stripe Connect',
        'wise' => 'Wise (TransferWise)',
        'manual' => 'Manual Payment',
    ],

    /*
    |--------------------------------------------------------------------------
    | Reporting
    |--------------------------------------------------------------------------
    |
    | Configure reporting and analytics settings.
    |
    */

    // Enable detailed commission breakdown in partner dashboard
    'detailed_reporting' => env('AFFILIATE_DETAILED_REPORTING', true),

    // Show upline information to direct partners
    'show_upline_info' => env('AFFILIATE_SHOW_UPLINE_INFO', false),

    // Show downline information to upline partners
    'show_downline_info' => env('AFFILIATE_SHOW_DOWNLINE_INFO', true),
];

// CLAUDE-CHECKPOINT
