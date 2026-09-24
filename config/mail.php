<?php

declare(strict_types=1);

return [
    'transport' => env('MAIL_TRANSPORT', 'log'),

    'from_address' => env('MAIL_FROM_ADDRESS', 'no-reply@lufly.test'),
    'from_name' => env('MAIL_FROM_NAME', 'LUFLY'),

    'reply_to_address' => env('MAIL_REPLY_TO', env('MAIL_FROM_ADDRESS', 'no-reply@lufly.test')),
    'reply_to_name' => env('MAIL_REPLY_TO_NAME', ''),

    'admin_address' => env('MAIL_ADMIN_ADDRESS', 'info@lufly.tr'),

    'smtp' => [
        'host' => env('MAIL_HOST', '127.0.0.1'),
        'port' => (int) env('MAIL_PORT', 587),
        'username' => env('MAIL_USERNAME', ''),
        'password' => env('MAIL_PASSWORD', ''),

        'encryption' => env('MAIL_ENCRYPTION', 'tls'),
        'auth' => (bool) env('MAIL_AUTH', true),
        'timeout' => (int) env('MAIL_TIMEOUT', 15),

        'helo' => env('MAIL_HELO', ''),
    ],
];
