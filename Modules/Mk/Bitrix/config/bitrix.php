<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Bitrix24 Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | The webhook URL from Bitrix24 REST API settings.
    | Format: https://xxx.bitrix24.com/rest/1/xxx/
    |
    */
    'webhook_base_url' => env('BITRIX24_WEBHOOK_BASE_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Shared Secret for Webhook Verification
    |--------------------------------------------------------------------------
    |
    | Secret key used to verify incoming webhook requests from Bitrix24.
    |
    */
    'shared_secret' => env('BITRIX_SHARED_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Outreach Rate Limits
    |--------------------------------------------------------------------------
    |
    | Rate limits for outreach email sending to avoid spam flags.
    |
    */
    'outreach' => [
        'daily_limit' => (int) env('OUTREACH_DAILY_LIMIT', 100),
        'hourly_limit' => (int) env('OUTREACH_HOURLY_LIMIT', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | Postmark Email Streams
    |--------------------------------------------------------------------------
    |
    | Separate streams for outreach vs transactional emails.
    |
    */
    'postmark' => [
        'stream_outreach' => env('POSTMARK_STREAM_OUTREACH', 'outreach'),
        'stream_transactional' => env('POSTMARK_STREAM_TRANSACTIONAL', 'transactional'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Lead Stages
    |--------------------------------------------------------------------------
    |
    | Mapping of internal stage names to Bitrix24 status IDs.
    | Configure these in Bitrix24 CRM settings.
    |
    */
    'lead_stages' => [
        'NEW' => 'NEW',
        'EMAILED' => 'UC_EMAILED',
        'OPENED' => 'UC_OPENED',
        'CLICKED' => 'UC_CLICKED',
        'REPLIED' => 'UC_REPLIED',
        'QUALIFIED' => 'PROCESSED',
        'LOST' => 'JUNK',
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Fields
    |--------------------------------------------------------------------------
    |
    | Mapping of field keys to Bitrix24 user field names.
    | These are created via bitrix:setup command.
    |
    */
    'custom_fields' => [
        'source' => 'UF_FCT_SOURCE',
        'source_url' => 'UF_FCT_SOURCE_URL',
        'city' => 'UF_FCT_CITY',
        'tags' => 'UF_FCT_TAGS',
        'partner_id' => 'UF_FCT_FACTURINO_PARTNER_ID',
        'last_postmark_message_id' => 'UF_FCT_LAST_POSTMARK_MESSAGE_ID',
        'outreach_sent_at' => 'UF_FCT_OUTREACH_SENT_AT',
        'outreach_stage' => 'UF_FCT_OUTREACH_STAGE',
    ],
];

// CLAUDE-CHECKPOINT
