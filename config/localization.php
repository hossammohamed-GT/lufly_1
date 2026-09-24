<?php

declare(strict_types=1);

return [

    'supported' => [
        'en' => 'English',
        'tr' => 'Türkçe',
        'cs' => 'Čeština',
    ],

    'default' => env('APP_LOCALE', 'en'),

    'fallback' => env('APP_FALLBACK_LOCALE', 'en'),

    'translatable' => [
        'products',
        'categories',
        'pages',
        'blogs',
    ],
];
