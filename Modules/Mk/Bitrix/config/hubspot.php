<?php

return [
    /*
    |--------------------------------------------------------------------------
    | HubSpot Private App Token
    |--------------------------------------------------------------------------
    | Get this from HubSpot: Settings → Integrations → Private Apps
    */
    'access_token' => env('HUBSPOT_PRIVATE_APP_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Pipeline Configuration
    |--------------------------------------------------------------------------
    | Run `php artisan hubspot:setup` to create the pipeline and get these IDs
    */
    'pipeline_id' => env('HUBSPOT_PIPELINE_ID'),

    'stages' => [
        'new_lead' => env('HUBSPOT_STAGE_NEW_LEAD'),
        'emailed' => env('HUBSPOT_STAGE_EMAILED'),
        'followup_due' => env('HUBSPOT_STAGE_FOLLOWUP_DUE'),
        'interested' => env('HUBSPOT_STAGE_INTERESTED'),
        'invite_sent' => env('HUBSPOT_STAGE_INVITE_SENT'),
        'partner_active' => env('HUBSPOT_STAGE_PARTNER_ACTIVE'),
        'lost' => env('HUBSPOT_STAGE_LOST'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Outreach Limits
    |--------------------------------------------------------------------------
    */
    'outreach' => [
        'daily_limit' => (int) env('OUTREACH_DAILY_LIMIT', 100),
        'hourly_limit' => (int) env('OUTREACH_HOURLY_LIMIT', 20),
        'min_send_interval_seconds' => 30,
        'followup_1_days' => 3,
        'followup_2_days' => 7,
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Property Names
    |--------------------------------------------------------------------------
    */
    'properties' => [
        'company' => [
            'fct_source',
            'fct_source_url',
            'fct_city',
            'fct_phone',
        ],
        'deal' => [
            'fct_last_touch_date',
            'fct_next_followup_date',
            'fct_partner_id',
        ],
    ],
];

// CLAUDE-CHECKPOINT
