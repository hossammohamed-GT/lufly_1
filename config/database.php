<?php

declare(strict_types=1);

return [
    'default' => env('DB_CONNECTION', 'sqlite'),

    /* Every query is written to storage/logs/database.log with a blocking
       LOCK_EX write. On the home page that is 10 file writes per request and
       it roughly doubled warm render time in profiling, so it is opt-in: set
       DB_LOG=true to trace queries while developing. */
    'log' => (bool) env('DB_LOG', false),

    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'lufly'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
        ],

        'sqlite' => [
            'driver' => 'sqlite',
            'database' => env('DB_SQLITE_PATH', 'database/lufly.sqlite'),
        ],
    ],

    'migrations_path' => 'database/migrations',
    'seeders_path' => 'database/seeders',
];
