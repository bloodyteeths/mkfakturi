<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    |
    | This option controls the default AI provider that will be used for
    | generating insights and answering questions. Supported: "claude", "openai", "gemini"
    |
    */
    'default_provider' => env('AI_PROVIDER', 'claude'),

    /*
    |--------------------------------------------------------------------------
    | AI Provider Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure the AI provider credentials and settings.
    | Each provider requires an API key which can be obtained from their
    | respective developer portals.
    |
    */
    'providers' => [
        'claude' => [
            'api_key' => env('CLAUDE_API_KEY'),
            'model' => env('CLAUDE_MODEL', 'claude-3-5-sonnet-20241022'),
            'api_url' => 'https://api.anthropic.com/v1/messages',
            'api_version' => '2023-06-01',
            'max_tokens' => env('CLAUDE_MAX_TOKENS', 4096),
            'temperature' => env('CLAUDE_TEMPERATURE', 0.7),
        ],

        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4-turbo'),
            'api_url' => 'https://api.openai.com/v1/chat/completions',
            'max_tokens' => env('OPENAI_MAX_TOKENS', 4096),
            'temperature' => env('OPENAI_TEMPERATURE', 0.7),
        ],

        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_MODEL', 'gemini-pro'),
            'api_url' => 'https://generativelanguage.googleapis.com/v1beta/models',
            'max_tokens' => env('GEMINI_MAX_TOKENS', 2048),
            'temperature' => env('GEMINI_TEMPERATURE', 0.7),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | AI insights are cached to reduce API costs and improve performance.
    | The cache TTL is specified in seconds.
    |
    */
    'cache_ttl' => env('AI_INSIGHTS_CACHE_TTL', 21600), // 6 hours

    /*
    |--------------------------------------------------------------------------
    | AI Insights Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for AI insights generation including prompt language
    | and analysis settings.
    |
    */
    'insights' => [
        'language' => 'macedonian', // Language for AI responses
        'max_insights' => 5, // Maximum number of insights to generate
        'priority_threshold' => 3, // Minimum priority level (1-5)
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Chat Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for interactive AI chat functionality.
    |
    */
    'chat' => [
        'max_history' => 10, // Maximum number of messages to keep in context
        'max_message_length' => 1000, // Maximum length of user messages
        'timeout' => 30, // API timeout in seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Cost Tracking
    |--------------------------------------------------------------------------
    |
    | Enable logging of all AI API calls for cost tracking and monitoring.
    |
    */
    'log_api_calls' => env('AI_LOG_API_CALLS', true),
    'log_channel' => env('AI_LOG_CHANNEL', 'stack'),
];
