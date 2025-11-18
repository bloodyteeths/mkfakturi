<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Prometheus Exporter Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for the Laravel Prometheus Exporter.
    | This file controls how metrics are collected and exposed.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Namespace
    |--------------------------------------------------------------------------
    |
    | The namespace for your Prometheus metrics. This will be prefixed to all
    | metric names to avoid conflicts with other applications.
    |
    */
    'namespace' => env('PROMETHEUS_NAMESPACE', 'invoiceshelf'),

    /*
    |--------------------------------------------------------------------------
    | Metrics Route
    |--------------------------------------------------------------------------
    |
    | The route where Prometheus metrics will be exposed. This should be
    | accessible by your Prometheus server for scraping.
    |
    */
    'metrics_route_path' => env('PROMETHEUS_METRICS_ROUTE_PATH', 'metrics'),

    /*
    |--------------------------------------------------------------------------
    | Metrics Route Enabled
    |--------------------------------------------------------------------------
    |
    | Whether to automatically register the metrics route. Set to false if
    | you want to manually define the route in your route files.
    |
    */
    'metrics_route_enabled' => env('PROMETHEUS_METRICS_ROUTE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Metrics Route Name
    |--------------------------------------------------------------------------
    |
    | The name for the metrics route. This can be used for generating URLs
    | or referencing the route in your application.
    |
    */
    'metrics_route_name' => env('PROMETHEUS_METRICS_ROUTE_NAME', 'metrics'),

    /*
    |--------------------------------------------------------------------------
    | Storage Adapter
    |--------------------------------------------------------------------------
    |
    | The storage adapter to use for storing metrics. Available options:
    | - memory: In-memory storage (default, good for single-process apps)
    | - redis: Redis storage (recommended for multi-process/server setups)
    | - apc: APC storage (good for single-server setups)
    |
    */
    'storage_adapter' => env('PROMETHEUS_STORAGE_ADAPTER', 'memory'),

    /*
    |--------------------------------------------------------------------------
    | Storage Adapter Configs
    |--------------------------------------------------------------------------
    |
    | Configuration options for different storage adapters.
    |
    */
    'storage_adapters' => [
        'memory' => [
            'adapter' => \Prometheus\Storage\InMemory::class,
        ],
        'redis' => [
            'adapter' => \Prometheus\Storage\Redis::class,
            'host' => env('PROMETHEUS_REDIS_HOST', env('REDIS_HOST', '127.0.0.1')),
            'port' => env('PROMETHEUS_REDIS_PORT', env('REDIS_PORT', 6379)),
            'password' => env('PROMETHEUS_REDIS_PASSWORD', env('REDIS_PASSWORD', null)),
            'database' => env('PROMETHEUS_REDIS_DATABASE', 2),
            'timeout' => env('PROMETHEUS_REDIS_TIMEOUT', 0.1),
            'read_timeout' => env('PROMETHEUS_REDIS_READ_TIMEOUT', 10),
            'persistent_connections' => env('PROMETHEUS_REDIS_PERSISTENT', false),
            'prefix' => env('PROMETHEUS_REDIS_PREFIX', 'PROMETHEUS_'),
        ],
        'apc' => [
            'adapter' => \Prometheus\Storage\APC::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Collect Default Metrics
    |--------------------------------------------------------------------------
    |
    | Whether to automatically collect default system metrics like memory
    | usage, request duration, etc. Set to false if you only want custom
    | metrics.
    |
    */
    'collect_default_metrics' => env('PROMETHEUS_COLLECT_DEFAULT_METRICS', true),

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware to apply to the metrics route. You might want to add
    | authentication or IP whitelisting for security.
    |
    */
    'route_middleware' => [
        // 'auth:api',
        // 'throttle:60,1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignore Routes
    |--------------------------------------------------------------------------
    |
    | Routes to ignore when collecting request metrics. This is useful for
    | excluding health checks, metrics endpoints, etc.
    |
    */
    'ignore_routes' => [
        'metrics',
        'health',
        'telescope*',
        '_debugbar*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignore Request Methods
    |--------------------------------------------------------------------------
    |
    | HTTP methods to ignore when collecting request metrics.
    |
    */
    'ignore_request_methods' => [
        'OPTIONS',
        'HEAD',
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Collectors
    |--------------------------------------------------------------------------
    |
    | Custom metric collectors to register. These should implement the
    | CollectorInterface and will be called on each metrics request.
    |
    */
    'collectors' => [
        // \App\Metrics\CustomCollector::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Business Metrics Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for business-specific metrics collection.
    |
    */
    'business_metrics' => [
        'enabled' => env('PROMETHEUS_BUSINESS_METRICS_ENABLED', true),
        'cache_ttl' => env('PROMETHEUS_BUSINESS_METRICS_CACHE_TTL', 300), // 5 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Metrics Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for performance metrics collection.
    |
    */
    'performance_metrics' => [
        'enabled' => env('PROMETHEUS_PERFORMANCE_METRICS_ENABLED', true),
        'track_database_queries' => env('PROMETHEUS_TRACK_DB_QUERIES', true),
        'track_cache_operations' => env('PROMETHEUS_TRACK_CACHE_OPS', true),
        'track_queue_jobs' => env('PROMETHEUS_TRACK_QUEUE_JOBS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Check Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the health check endpoint.
    |
    */
    'health_check' => [
        'enabled' => env('PROMETHEUS_HEALTH_CHECK_ENABLED', true),
        'route_path' => env('PROMETHEUS_HEALTH_ROUTE_PATH', 'health'),
        'route_name' => env('PROMETHEUS_HEALTH_ROUTE_NAME', 'health'),
        'checks' => [
            'database' => true,
            'cache' => true,
            'storage' => true,
            'queue' => true,
        ],
    ],
];
