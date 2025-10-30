<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => env('LOG_DEPRECATIONS_TRACE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog", "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', env('LOG_STACK', 'single')),
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 14),
            'replace_placeholders' => true,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => env('LOG_SLACK_USERNAME', 'Laravel Log'),
            'emoji' => env('LOG_SLACK_EMOJI', ':boom:'),
            'level' => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => env('LOG_SYSLOG_FACILITY', LOG_USER),
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Custom Application Channels
        |--------------------------------------------------------------------------
        |
        | These are custom channels for specific application functionality
        | providing structured logging with appropriate context and formatting.
        |
        */

        'critical' => [
            'driver' => 'daily',
            'path' => storage_path('logs/critical.log'),
            'level' => 'critical',
            'days' => 30,
            'replace_placeholders' => true,
        ],

        'security' => [
            'driver' => 'daily',
            'path' => storage_path('logs/security.log'),
            'level' => 'info',
            'days' => 90,
            'replace_placeholders' => true,
        ],

        'audit' => [
            'driver' => 'daily',
            'path' => storage_path('logs/audit.log'),
            'level' => 'info',
            'days' => 365, // Keep for compliance requirements
            'replace_placeholders' => true,
        ],

        'migration' => [
            'driver' => 'daily',
            'path' => storage_path('logs/migration.log'),
            'level' => 'debug',
            'days' => 30,
            'replace_placeholders' => true,
        ],

        'payment' => [
            'driver' => 'daily',
            'path' => storage_path('logs/payment.log'),
            'level' => 'info',
            'days' => 365, // Keep for financial compliance
            'replace_placeholders' => true,
        ],

        'bank_integration' => [
            'driver' => 'daily',
            'path' => storage_path('logs/bank_integration.log'),
            'level' => 'info',
            'days' => 90,
            'replace_placeholders' => true,
        ],

        'api' => [
            'driver' => 'daily',
            'path' => storage_path('logs/api.log'),
            'level' => 'info',
            'days' => 30,
            'replace_placeholders' => true,
        ],

        'performance' => [
            'driver' => 'daily',
            'path' => storage_path('logs/performance.log'),
            'level' => 'warning',
            'days' => 7,
            'replace_placeholders' => true,
        ],

        'user_activity' => [
            'driver' => 'daily',
            'path' => storage_path('logs/user_activity.log'),
            'level' => 'info',
            'days' => 30,
            'replace_placeholders' => true,
        ],

        'business_logic' => [
            'driver' => 'daily',
            'path' => storage_path('logs/business_logic.log'),
            'level' => 'warning',
            'days' => 30,
            'replace_placeholders' => true,
        ],

        'queue' => [
            'driver' => 'daily',
            'path' => storage_path('logs/queue.log'),
            'level' => 'info',
            'days' => 14,
            'replace_placeholders' => true,
        ],

        'database' => [
            'driver' => 'daily',
            'path' => storage_path('logs/database.log'),
            'level' => 'warning',
            'days' => 7,
            'replace_placeholders' => true,
        ],

        'external_services' => [
            'driver' => 'daily',
            'path' => storage_path('logs/external_services.log'),
            'level' => 'info',
            'days' => 14,
            'replace_placeholders' => true,
        ],

        /*
        |--------------------------------------------------------------------------
        | Environment-Specific Channels
        |--------------------------------------------------------------------------
        |
        | Different logging configurations for different environments
        |
        */

        'production' => [
            'driver' => 'stack',
            'channels' => ['daily', 'critical', 'security', 'audit', 'slack_critical', 'email_critical'],
            'ignore_exceptions' => false,
        ],

        'production_with_monitoring' => [
            'driver' => 'stack',
            'channels' => ['daily', 'critical', 'security', 'audit', 'sentry', 'slack_critical', 'email_critical'],
            'ignore_exceptions' => false,
        ],

        'staging' => [
            'driver' => 'stack',
            'channels' => ['daily', 'critical'],
            'ignore_exceptions' => false,
        ],

        'testing' => [
            'driver' => 'single',
            'path' => storage_path('logs/testing.log'),
            'level' => 'debug',
        ],

        /*
        |--------------------------------------------------------------------------
        | Monitoring Integration Channels
        |--------------------------------------------------------------------------
        |
        | Integration with external monitoring services
        |
        */

        'sentry' => [
            'driver' => 'monolog',
            'handler' => \Sentry\Monolog\Handler::class,
            'level' => 'error',
            'formatter' => 'default',
            'processors' => [
                \Monolog\Processor\IntrospectionProcessor::class,
                \Monolog\Processor\WebProcessor::class,
                \Monolog\Processor\MemoryUsageProcessor::class,
            ],
        ],

        'slack_critical' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_CRITICAL_WEBHOOK_URL'),
            'username' => env('LOG_SLACK_USERNAME', 'Critical Alerts'),
            'emoji' => env('LOG_SLACK_EMOJI', ':fire:'),
            'level' => 'critical',
            'replace_placeholders' => true,
        ],

        'slack_errors' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_ERRORS_WEBHOOK_URL'),
            'username' => env('LOG_SLACK_USERNAME', 'Error Monitor'),
            'emoji' => env('LOG_SLACK_EMOJI', ':warning:'),
            'level' => 'error',
            'replace_placeholders' => true,
        ],

        'email_critical' => env('APP_ENV') === 'testing' ? [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => 'critical',
        ] : [
            'driver' => 'single',
            'path' => storage_path('logs/critical.log'),
            'level' => 'critical',
        ],

        'datadog' => [
            'driver' => 'monolog',
            'handler' => \Monolog\Handler\SocketHandler::class,
            'handler_with' => [
                'connectionString' => env('DATADOG_LOG_ENDPOINT', 'tcp://intake.logs.datadoghq.com:10516'),
            ],
            'level' => 'info',
            'processors' => [
                \Monolog\Processor\IntrospectionProcessor::class,
                \Monolog\Processor\WebProcessor::class,
                \Monolog\Processor\MemoryUsageProcessor::class,
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Structured JSON Logging
        |--------------------------------------------------------------------------
        |
        | JSON formatted logs for machine parsing and analysis
        |
        */

        'json' => [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'formatter' => \Monolog\Formatter\JsonFormatter::class,
            'with' => [
                'stream' => storage_path('logs/structured.log'),
            ],
            'level' => env('LOG_LEVEL', 'debug'),
            'processors' => [
                PsrLogMessageProcessor::class,
                \Monolog\Processor\IntrospectionProcessor::class,
                \Monolog\Processor\WebProcessor::class,
                \Monolog\Processor\MemoryUsageProcessor::class,
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Agent Framework Channels
        |--------------------------------------------------------------------------
        |
        | Specialized logging channels for the multi-agent debugging framework
        |
        */

        'agent_performance' => [
            'driver' => 'daily',
            'path' => storage_path('logs/agents/performance.log'),
            'level' => 'debug',
            'days' => 14,
            'replace_placeholders' => true,
        ],

        'agent_database' => [
            'driver' => 'daily',
            'path' => storage_path('logs/agents/database.log'),
            'level' => 'debug',
            'days' => 14,
            'replace_placeholders' => true,
        ],

        'agent_security' => [
            'driver' => 'daily',
            'path' => storage_path('logs/agents/security.log'),
            'level' => 'debug',
            'days' => 30,
            'replace_placeholders' => true,
        ],

        'agent_testing' => [
            'driver' => 'daily',
            'path' => storage_path('logs/agents/testing.log'),
            'level' => 'debug',
            'days' => 7,
            'replace_placeholders' => true,
        ],

        'agent_metrics' => [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'formatter' => \Monolog\Formatter\JsonFormatter::class,
            'with' => [
                'stream' => storage_path('logs/agents/metrics.json'),
            ],
            'level' => 'info',
            'processors' => [
                PsrLogMessageProcessor::class,
                \Monolog\Processor\IntrospectionProcessor::class,
                \Monolog\Processor\WebProcessor::class,
                \Monolog\Processor\MemoryUsageProcessor::class,
            ],
        ],

        'agent_coordination' => [
            'driver' => 'daily',
            'path' => storage_path('logs/agents/coordination.log'),
            'level' => 'info',
            'days' => 7,
            'replace_placeholders' => true,
        ],

        /*
        |--------------------------------------------------------------------------
        | Development Channels
        |--------------------------------------------------------------------------
        |
        | Channels specifically for development and debugging
        |
        */

        'debug' => [
            'driver' => 'single',
            'path' => storage_path('logs/debug.log'),
            'level' => 'debug',
        ],

        'sql' => [
            'driver' => 'daily',
            'path' => storage_path('logs/sql.log'),
            'level' => 'debug',
            'days' => 3,
        ],
    ],

];