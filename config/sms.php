<?php

declare(strict_types=1);

return [
    'default' => env('SMS_DRIVER', 'provider'),

    'drivers' => [
        'provider' => [
            'base_url' => env('SMS_PROVIDER_URL', ''),
            'api_key' => env('SMS_PROVIDER_API_KEY', ''),
            'api_key_header' => env('SMS_PROVIDER_API_KEY_HEADER', 'X-API-Key'),
            'sender' => env('SMS_PROVIDER_SENDER'),
            'connect_timeout' => (int) env('SMS_PROVIDER_CONNECT_TIMEOUT', 3),
            'timeout' => (int) env('SMS_PROVIDER_TIMEOUT', 10),
            'retries' => (int) env('SMS_PROVIDER_RETRIES', 2),
            'retry_delay' => (int) env('SMS_PROVIDER_RETRY_DELAY', 200),
        ],
    ],

    'queue' => [
        'connection' => env('SMS_QUEUE_CONNECTION'),
        'queue' => env('SMS_QUEUE'),
        'tries' => (int) env('SMS_QUEUE_TRIES', 3),
        'backoff' => [10, 60, 300],
    ],
];
