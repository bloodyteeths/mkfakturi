<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Support Email Address
    |--------------------------------------------------------------------------
    |
    | This is the email address where support contact notifications will be sent.
    | All support inquiries from the contact form will be delivered to this address.
    |
    */
    'email' => env('SUPPORT_EMAIL', 'support@facturino.mk'),

    /*
    |--------------------------------------------------------------------------
    | Expected Response Time (Hours)
    |--------------------------------------------------------------------------
    |
    | This value determines the expected response time that will be communicated
    | to users in the auto-reply email. It's used for setting expectations.
    |
    */
    'response_time_hours' => env('SUPPORT_RESPONSE_TIME', 48),

    /*
    |--------------------------------------------------------------------------
    | Maximum Attachment Size (Bytes)
    |--------------------------------------------------------------------------
    |
    | The maximum file size allowed for attachments in support contact submissions.
    | Default is 5MB (5 * 1024 * 1024 bytes).
    |
    */
    'max_attachment_size' => 5 * 1024 * 1024, // 5MB

    /*
    |--------------------------------------------------------------------------
    | Allowed Attachment File Types
    |--------------------------------------------------------------------------
    |
    | An array of allowed file extensions for support contact attachments.
    | Only these file types will be accepted by the validation.
    |
    */
    'allowed_attachment_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf'],

    /*
    |--------------------------------------------------------------------------
    | Support Categories
    |--------------------------------------------------------------------------
    |
    | Available categories for support inquiries. These are used in the
    | contact form dropdown and for categorizing support tickets.
    |
    */
    'categories' => [
        'technical' => 'Technical Issue',
        'billing' => 'Billing Question',
        'feature' => 'Feature Request',
        'general' => 'General Inquiry',
    ],

    /*
    |--------------------------------------------------------------------------
    | Priority Levels
    |--------------------------------------------------------------------------
    |
    | Available priority levels for support inquiries. Users can select
    | these when submitting a support contact form.
    |
    */
    'priorities' => [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent',
    ],
];
// CLAUDE-CHECKPOINT
