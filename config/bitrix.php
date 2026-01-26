<?php

return [
    'webhook_base_url' => env('BITRIX24_WEBHOOK_BASE_URL'), // e.g. https://xxx.bitrix24.com/rest/1/xxx/
    'shared_secret' => env('BITRIX_SHARED_SECRET'),

    'lead_stages' => [
        'NEW' => 'NEW',
        'EMAILED' => 'UC_EMAILED',
        'FOLLOWUP' => 'UC_FOLLOWUP',
        'INTERESTED' => 'UC_INTERESTED',
        'INVITE_SENT' => 'UC_INVITE_SENT',
        'PARTNER_CREATED' => 'UC_PARTNER_CREATED',
        'ACTIVE' => 'UC_ACTIVE',
        'LOST' => 'UC_LOST',
    ],

    'custom_fields' => [
        'source' => 'UF_FCT_SOURCE',
        'source_url' => 'UF_FCT_SOURCE_URL',
        'city' => 'UF_FCT_CITY',
        'tags' => 'UF_FCT_TAGS',
        'partner_id' => 'UF_FCT_FACTURINO_PARTNER_ID',
        'last_postmark_message_id' => 'UF_FCT_LAST_POSTMARK_MESSAGE_ID',
    ],

    'outreach' => [
        'daily_limit' => (int) env('OUTREACH_DAILY_LIMIT', 100),
        'hourly_limit' => (int) env('OUTREACH_HOURLY_LIMIT', 20),
        'min_send_interval_seconds' => 30, // jitter between sends
    ],
];

// CLAUDE-CHECKPOINT
