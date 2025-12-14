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
    | Streaming Settings
    |--------------------------------------------------------------------------
    |
    | Enable or disable streaming responses for real-time AI chat.
    |
    */
    'enable_streaming' => env('AI_STREAMING', true),

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

    /*
    |--------------------------------------------------------------------------
    | Model Routing
    |--------------------------------------------------------------------------
    |
    | Configure which models to use for different operation types.
    | Classification uses Haiku for 10x cheaper intent detection.
    |
    */
    'model_routing' => [
        'classification' => env('AI_MODEL_CLASSIFICATION', 'claude-3-haiku-20240307'),
        'chat' => env('AI_MODEL_CHAT', 'claude-3-5-sonnet-20241022'),
        'analysis' => env('AI_MODEL_ANALYSIS', 'claude-3-5-sonnet-20241022'),
        'vision' => env('AI_MODEL_VISION', 'claude-3-5-sonnet-20241022'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Fast Classification
    |--------------------------------------------------------------------------
    |
    | Use Haiku model for fast intent classification to reduce costs.
    |
    */
    'use_fast_classification' => env('AI_USE_FAST_CLASSIFICATION', true),

    /*
    |--------------------------------------------------------------------------
    | Prompt Caching
    |--------------------------------------------------------------------------
    |
    | Enable Anthropic's prompt caching feature to reduce costs by 90% and
    | latency by 85%. This caches static system prompts across API calls.
    |
    */
    'enable_prompt_caching' => env('AI_PROMPT_CACHING', true),

    /*
    |--------------------------------------------------------------------------
    | MCP Server Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Model Context Protocol server that provides
    | financial data and tools to the AI.
    |
    */
    'mcp' => [
        'url' => env('MCP_SERVER_URL', 'http://localhost:8080'),
        'token' => env('MCP_SERVER_TOKEN'),
        'timeout' => env('MCP_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | PDF to Image Converter
    |--------------------------------------------------------------------------
    |
    | Configuration for converting PDF documents to images for AI vision analysis.
    | Supported backends: 'imagick', 'external_api'
    |
    | Note: Imagick backend requires the Imagick PHP extension to be installed.
    | Install with: pecl install imagick (requires ImageMagick and Ghostscript)
    |
    */
    'pdf_converter_backend' => env('PDF_CONVERTER_BACKEND', 'imagick'),
    'pdf_converter_dpi' => env('PDF_CONVERTER_DPI', 150), // Higher DPI = better quality, larger files
    'pdf_converter_format' => env('PDF_CONVERTER_FORMAT', 'png'), // png, jpg, webp

    /*
    |--------------------------------------------------------------------------
    | Vision Analysis Features
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific vision-based AI features.
    |
    */
    'features' => [
        'pdf_analysis' => env('AI_FEATURE_PDF_ANALYSIS', false),
        'receipt_scanning' => env('AI_FEATURE_RECEIPT_SCANNING', false),
        'invoice_extraction' => env('AI_FEATURE_INVOICE_EXTRACTION', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for retry logic with exponential backoff for handling
    | transient API failures such as rate limits and server errors.
    |
    */
    'retry' => [
        'max_attempts' => env('AI_RETRY_MAX_ATTEMPTS', 3),
        'initial_delay_ms' => env('AI_RETRY_INITIAL_DELAY', 1000),
        'multiplier' => env('AI_RETRY_MULTIPLIER', 2),
        'retryable_status_codes' => [429, 500, 502, 503, 529],
    ],
];

// CLAUDE-CHECKPOINT
