<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Intelligent Import System
    |--------------------------------------------------------------------------
    |
    | Enable the intelligent field mapping system with multi-language support,
    | fuzzy matching, and automatic data type detection.
    |
    */

    'intelligent_enabled' => env('INTELLIGENT_IMPORT_ENABLED', false),

    'intelligent' => [
        'matchers' => [
            'exact' => ['enabled' => true, 'weight' => 1.0],
            'synonym' => ['enabled' => true, 'weight' => 0.90],
            'fuzzy' => ['enabled' => true, 'weight' => 0.80],
            'pattern' => ['enabled' => true, 'weight' => 0.75],
        ],

        'confidence_threshold' => env('INTELLIGENT_IMPORT_MIN_CONFIDENCE', 0.60),

        'quality_thresholds' => [
            'excellent' => 90,
            'good' => 75,
            'fair' => 60,
            'poor' => 40,
        ],

        'fallback_to_legacy' => env('INTELLIGENT_IMPORT_FALLBACK', true),

        'logging' => [
            'enabled' => env('INTELLIGENT_IMPORT_LOGGING', true),
            'channel' => env('LOG_CHANNEL', 'stack'),
        ],
    ],

];
// CLAUDE-CHECKPOINT
