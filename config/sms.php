<?php

declare(strict_types=1);

return [
    'default' => env('SMS_DRIVER', 'onnorokom'),

    'drivers' => [
        'onnorokom' => [
            'base_url' => env('SMS_ONNOROKOM_URL', 'https://api2.onnorokomsms.com/sendsms.asmx'),
            'api_key' => env('SMS_ONNOROKOM_API_KEY', ''),
            'sender' => env('SMS_ONNOROKOM_SENDER'),
            'connect_timeout' => (int) env('SMS_ONNOROKOM_CONNECT_TIMEOUT', 3),
            'timeout' => (int) env('SMS_ONNOROKOM_TIMEOUT', 10),
            'retries' => (int) env('SMS_ONNOROKOM_RETRIES', 2),
            'retry_delay' => (int) env('SMS_ONNOROKOM_RETRY_DELAY', 200),
        ],
    ],

    'queue' => [
        'connection' => env('SMS_QUEUE_CONNECTION'),
        'queue' => env('SMS_QUEUE'),
        'tries' => (int) env('SMS_QUEUE_TRIES', 3),
        'backoff' => [10, 60, 300],
    ],
];
