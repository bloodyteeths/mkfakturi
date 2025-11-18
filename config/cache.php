<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    |
    | This option controls the default cache connection that gets used while
    | using this caching library. This connection is used when another is
    | not explicitly specified when executing a given caching function.
    |
    */

    'default' => env('CACHE_STORE', env('CACHE_DRIVER', 'file')),

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the cache "stores" for your application as
    | well as their drivers. You may even define multiple stores for the
    | same cache driver to group types of items stored in your caches.
    |
    */

    'stores' => [
        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'cache',
            'connection' => null,
            'lock_connection' => null,
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'lock_path' => storage_path('framework/cache/data'),
        ],

        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
            'sasl' => [
                env('MEMCACHED_USERNAME'),
                env('MEMCACHED_PASSWORD'),
            ],
            'options' => [
                // Memcached::OPT_CONNECT_TIMEOUT => 2000,
            ],
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('CACHE_REDIS_CONNECTION', 'cache'),
            'lock_connection' => env('CACHE_REDIS_LOCK_CONNECTION', 'default'),
        ],

        'dynamodb' => [
            'driver' => 'dynamodb',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'table' => env('DYNAMODB_CACHE_TABLE', 'cache'),
            'endpoint' => env('DYNAMODB_ENDPOINT'),
        ],

        'octane' => [
            'driver' => 'octane',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | When utilizing the APC, database, memcached, Redis, or DynamoDB cache
    | stores, there might be other applications using the same cache. For
    | that reason, you may prefix every cache key to avoid collisions.
    |
    */

    'prefix' => env('CACHE_PREFIX', 'mkaccounting_cache'),

    /*
    |--------------------------------------------------------------------------
    | Cache Performance Settings
    |--------------------------------------------------------------------------
    |
    | Performance-specific cache settings for optimization
    |
    */

    'performance' => [
        // Enable/disable cache compression
        'compression' => env('CACHE_COMPRESSION', true),

        // Cache serialization method
        'serialization' => env('CACHE_SERIALIZATION', 'php'),

        // Maximum cache entry size (in bytes)
        'max_entry_size' => env('CACHE_MAX_ENTRY_SIZE', 1048576), // 1MB

        // Cache tags support (Redis only)
        'tags_enabled' => env('CACHE_TAGS_ENABLED', true),

        // Cache statistics collection
        'stats_enabled' => env('CACHE_STATS_ENABLED', true),

        // Automatic cache warming
        'auto_warm' => env('CACHE_AUTO_WARM', true),

        // Performance monitoring
        'monitor_slow_queries' => env('CACHE_MONITOR_SLOW_QUERIES', true),
        'slow_query_threshold_ms' => env('CACHE_SLOW_QUERY_THRESHOLD', 100),

        // Exchange rate caching
        'exchange_rate_ttl' => env('EXCHANGE_RATE_CACHE_TTL', 3600), // 1 hour
        'exchange_rate_api_timeout' => env('EXCHANGE_RATE_API_TIMEOUT', 10), // seconds

        // Query result caching
        'query_cache_enabled' => env('QUERY_CACHE_ENABLED', true),
        'query_cache_default_ttl' => env('QUERY_CACHE_TTL', 900), // 15 minutes

        // Dashboard caching
        'dashboard_cache_ttl' => env('DASHBOARD_CACHE_TTL', 600), // 10 minutes

        // Company settings cache
        'company_settings_ttl' => env('COMPANY_SETTINGS_CACHE_TTL', 86400), // 24 hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Invalidation Settings
    |--------------------------------------------------------------------------
    |
    | Settings for automatic cache invalidation strategies
    |
    */

    'invalidation' => [
        // Automatic invalidation on model updates
        'auto_invalidate_models' => env('CACHE_AUTO_INVALIDATE_MODELS', true),

        // Model-specific invalidation patterns
        'model_patterns' => [
            'Customer' => ['customers', 'dashboard', 'stats'],
            'Invoice' => ['invoices', 'dashboard', 'stats', 'aggregation'],
            'Payment' => ['payments', 'dashboard', 'stats', 'aggregation'],
            'Item' => ['items', 'dashboard'],
            'CompanySetting' => ['settings', 'company'],
        ],

        // Time-based invalidation
        'time_based_patterns' => [
            'dashboard:*' => 600, // 10 minutes
            'aggregation:*' => 300, // 5 minutes
            'search:*' => 1800, // 30 minutes
        ],
    ],
];

// CLAUDE-CHECKPOINT
