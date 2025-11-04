<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Namespace
    |--------------------------------------------------------------------------
    |
    | The namespace for the metrics. This will be prepended to all metric names.
    |
    */

    'namespace' => env('PROMETHEUS_NAMESPACE', 'fakturino'),

    /*
    |--------------------------------------------------------------------------
    | Metrics Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be applied to the metrics route.
    |
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Metrics Route Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where the metrics will be accessible from.
    |
    */

    'route_path' => env('PROMETHEUS_ROUTE_PATH', 'metrics'),

    /*
    |--------------------------------------------------------------------------
    | Storage Adapter
    |--------------------------------------------------------------------------
    |
    | The storage adapter to use for storing metrics. Options are: memory, redis, apc
    |
    */

    'storage_adapter' => env('PROMETHEUS_STORAGE_ADAPTER', 'memory'),

    /*
    |--------------------------------------------------------------------------
    | Redis Connection
    |--------------------------------------------------------------------------
    |
    | The Redis connection to use when using the Redis storage adapter.
    |
    */

    'redis_connection' => env('PROMETHEUS_REDIS_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Collectors
    |--------------------------------------------------------------------------
    |
    | Here you can specify which collectors to enable.
    |
    */

    'collectors' => [
        //
    ],
];
