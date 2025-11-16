<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection Name
    |--------------------------------------------------------------------------
    |
    | Laravel's queue supports various back-ends for sending queue jobs.
    | Here you may set which of the following connections will be used as
    | your default queue connection for all queue work.
    |
    */

    // Default queue driver:
    // - If FEATURE_REDIS_QUEUES=true, prefer Redis (requires Redis service).
    // - Otherwise fall back to database driver for compatibility.
    'default' => env(
        'QUEUE_CONNECTION',
        env('FEATURE_REDIS_QUEUES', false) ? 'redis' : 'database'
    ),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the queue connections for your application. When
    | you are ready, uncomment the connection you wish to use and configure
    | any queue that you like.
    |
    */

    'connections' => [
        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
            'after_commit' => false,
        ],

        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => 'localhost',
            'queue' => 'default',
            'retry_after' => 90,
            'block_for' => 0,
            'after_commit' => false,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue' => env('SQS_QUEUE', 'default'),
            'suffix' => env('SQS_SUFFIX'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'after_commit' => false,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('QUEUE_REDIS_CONNECTION', 'queue'),
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => 90,
            'block_for' => null,
            'after_commit' => false,
        ],

        // High priority queue for critical operations
        'high' => [
            'driver' => 'redis',
            'connection' => env('QUEUE_REDIS_CONNECTION', 'queue'),
            'queue' => 'high',
            'retry_after' => 90,
            'block_for' => null,
            'after_commit' => false,
        ],

        // Migration queue for data import operations
        'migration' => [
            'driver' => 'redis',
            'connection' => env('QUEUE_REDIS_CONNECTION', 'queue'),
            'queue' => 'migration',
            'retry_after' => 600, // 10 minutes for large data processing
            'block_for' => null,
            'after_commit' => false,
        ],

        // Background queue for low priority tasks
        'background' => [
            'driver' => 'redis',
            'connection' => env('QUEUE_REDIS_CONNECTION', 'queue'),
            'queue' => 'background',
            'retry_after' => 90,
            'block_for' => null,
            'after_commit' => false,
        ],

        // E-invoice submission queue for tax authority submissions
        'einvoice' => [
            'driver' => 'redis',
            'connection' => env('QUEUE_REDIS_CONNECTION', 'queue'),
            'queue' => 'einvoice',
            'retry_after' => 150, // 2.5 minutes (longer than job timeout)
            'block_for' => null,
            'after_commit' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Batching
    |--------------------------------------------------------------------------
    |
    | The following options configure the job batching features for your
    | application. By default, job batching is disabled unless you have
    | enabled a compatible queue driver.
    |
    */

    'batching' => [
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'job_batches',
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue jobs so you can
    | control which database and table are used to store the jobs that have
    | failed. You may change them to any database / table you wish.
    |
    */

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database'),
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'failed_jobs',
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Queue performance optimization settings
    |
    */

    'performance' => [
        // Number of jobs to process per batch
        'batch_size' => env('QUEUE_BATCH_SIZE', 100),
        
        // Maximum execution time per job (seconds)
        'max_execution_time' => env('QUEUE_MAX_EXECUTION_TIME', 300),
        
        // Memory limit per worker (MB)
        'memory_limit' => env('QUEUE_MEMORY_LIMIT', 512),
        
        // Sleep time when no jobs available (seconds)
        'sleep' => env('QUEUE_SLEEP', 3),
        
        // Number of times to attempt a job before failing
        'max_tries' => env('QUEUE_MAX_TRIES', 3),
        
        // Backoff delays between retries (seconds)
        'backoff' => [10, 30, 60],
    ],
];

// CLAUDE-CHECKPOINT
