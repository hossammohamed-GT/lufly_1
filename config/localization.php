<?php

declare(strict_types=1);

return [
    // Languages can be added here (and in the languages table) without code changes.
    'supported' => [
        'en' => 'English',
        'tr' => 'Türkçe',
        'cs' => 'Čeština',
    ],

    'default' => env('APP_LOCALE', 'en'),

    'fallback' => env('APP_FALLBACK_LOCALE', 'en'),

    // Entities that support dynamic translations (*_translations tables).
    'translatable' => [
        'products',
        'categories',
        'pages',
        'blogs',
    ],
];
