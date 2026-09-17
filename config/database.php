<?php

declare(strict_types=1);

return [
    'default' => env('DB_CONNECTION', 'sqlite'),

    'log' => true,

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
