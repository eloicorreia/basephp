<?php

declare(strict_types=1);

$environment = (string) env('APP_ENV', 'production');
$isProduction = $environment === 'production';

return [
    /*
    |--------------------------------------------------------------------------
    | API Request Logging
    |--------------------------------------------------------------------------
    |
    | Keep operational metadata always useful, but avoid persisting full
    | request/response bodies in production unless the project explicitly opts
    | in. Sanitization is still applied before anything reaches the database.
    |
    */

    'api_request_logging' => [
        'enabled' => env('API_REQUEST_LOGGING_ENABLED', true),
        'store_headers' => env('API_REQUEST_LOGGING_STORE_HEADERS', true),
        'store_query' => env('API_REQUEST_LOGGING_STORE_QUERY', ! $isProduction),
        'store_request_body' => env('API_REQUEST_LOGGING_STORE_REQUEST_BODY', ! $isProduction),
        'store_response_body' => env('API_REQUEST_LOGGING_STORE_RESPONSE_BODY', ! $isProduction),
        'max_text_length' => (int) env('API_REQUEST_LOGGING_MAX_TEXT_LENGTH', 2000),

        'allowed_headers' => [
            'accept',
            'content-type',
            'user-agent',
            'x-request-id',
            'x-trace-id',
            'x-tenant-id',
            'authorization',
            'x-api-key',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Log Retention
    |--------------------------------------------------------------------------
    |
    | Values <= 0 disable pruning for a table. Audit logs intentionally keep a
    | longer default retention because they usually carry compliance value.
    |
    */

    'retention' => [
        'enabled' => env('LOG_PRUNING_ENABLED', true),
        'schedule_time' => env('LOG_PRUNING_SCHEDULE_TIME', '02:15'),

        'public_tables' => [
            'api_request_logs' => [
                'column' => 'created_at',
                'days' => (int) env('LOG_RETENTION_API_REQUEST_DAYS', 30),
            ],
            'system_logs' => [
                'column' => 'created_at',
                'days' => (int) env('LOG_RETENTION_SYSTEM_DAYS', 90),
            ],
            'authentication_logs' => [
                'column' => 'created_at',
                'days' => (int) env('LOG_RETENTION_AUTHENTICATION_DAYS', 180),
            ],
            'audit_logs' => [
                'column' => 'created_at',
                'days' => (int) env('LOG_RETENTION_AUDIT_DAYS', 365),
            ],
            'queue_execution_logs' => [
                'column' => 'occurred_at',
                'days' => (int) env('LOG_RETENTION_QUEUE_EXECUTION_DAYS', 30),
            ],
            'queue_job_logs' => [
                'column' => 'created_at',
                'days' => (int) env('LOG_RETENTION_QUEUE_JOB_DAYS', 30),
            ],
            'queue_worker_logs' => [
                'column' => 'created_at',
                'days' => (int) env('LOG_RETENTION_QUEUE_WORKER_DAYS', 30),
            ],
        ],
    ],
];
