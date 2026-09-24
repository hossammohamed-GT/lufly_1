<?php

declare(strict_types=1);

return [
    'enabled' => (bool) env('ASSISTANT_ENABLED', true),

    'chat' => [
        'enabled' => (bool) env('ASSISTANT_CHAT', true),
        'max_chars' => (int) env('ASSISTANT_MAX_CHARS', 600),
        'daily_per_ip' => (int) env('ASSISTANT_DAILY', 20),
    ],

    'find' => [
        'limit' => (int) env('ASSISTANT_LIMIT', 6),
        'terms' => (int) env('ASSISTANT_TERMS', 8),
        'scanned' => 24,
        'good_score' => (int) env('ASSISTANT_GOOD_SCORE', 24),
        'min_score' => 3,
    ],

    'photo' => [
        'enabled' => (bool) env('ASSISTANT_PHOTO', true),
        'max_kb' => (int) env('ASSISTANT_PHOTO_KB', 4096),
        'min_side' => 160,
        'types' => ['image/jpeg', 'image/png', 'image/webp'],
        'folder' => 'uploads/assistant',
    ],

    'lead' => [
        'email' => (string) env('ASSISTANT_LEAD_MAIL', 'hossam545mohamed@gmail.com'),
        'default_email' => (string) env('ASSISTANT_LEAD_DEFAULT', 'hossam545mohamed@gmail.com'),
        'ask_email' => (bool) env('ASSISTANT_ASK_EMAIL', true),
        'notify_text' => (bool) env('ASSISTANT_LEAD_TEXT', false),
    ],

    'build' => (string) env('ASSISTANT_BUILD', '2026-09-24.7'),

    'ai' => [
        'enabled' => (bool) env('ASSISTANT_AI', true),
        'vision' => (bool) env('ASSISTANT_VISION', true),
        'model' => (string) env('ASSISTANT_AI_MODEL', ''),
        'max_terms' => 5,
    ],

    'guide' => [
        'enabled' => (bool) env('ASSISTANT_GUIDE', true),

        'topics' => [
            'basin' => ['category' => 'bathroom-ceramics', 'terms' => ['washbasin']],
            'toilet' => ['category' => 'bathroom-ceramics', 'terms' => ['wc']],
            'shower' => ['category' => 'shower-sets', 'terms' => ['shower']],
            'tap' => ['category' => '', 'terms' => ['mixer']],
            'access' => ['category' => 'accessible-range', 'terms' => ['accessible']],
            'kids' => ['category' => 'kids', 'terms' => ['children']],
            'bath' => ['category' => '', 'terms' => ['bath']],
        ],

        'options' => [
            'basin' => [
                'small' => ['terms' => ['washbasin', 'small']],
                'wide' => ['terms' => ['washbasin', '60']],
                'counter' => ['terms' => ['washbasin', 'desk']],
                'sensor' => ['terms' => ['sensor', 'tap']],
                'show' => ['terms' => ['washbasin']],
            ],
            'toilet' => [
                'hung' => ['terms' => ['wc', 'wall', 'hung']],
                'floor' => ['terms' => ['wc', 'floor']],
                'show' => ['terms' => ['wc']],
            ],
            'shower' => [
                'set' => ['terms' => ['shower', 'mixer']],
                'concealed' => ['terms' => ['concealed', 'shower']],
                'sensor' => ['terms' => ['sensor', 'shower']],
                'show' => ['terms' => ['shower']],
            ],
            'tap' => [
                'basin' => ['terms' => ['basin', 'mixer']],
                'sink' => ['terms' => ['sink', 'mixer']],
                'sensor' => ['terms' => ['sensor', 'tap']],
                'show' => ['terms' => ['mixer']],
            ],
            'access' => [
                'seat' => ['terms' => ['accessible', 'seat']],
                'rail' => ['terms' => ['grab', 'bar']],
                'basin' => ['terms' => ['accessible', 'basin']],
                'show' => ['terms' => ['accessible']],
            ],
            'kids' => [
                'basin' => ['terms' => ['children', 'washbasin']],
                'seat' => ['terms' => ['children', 'seat']],
                'show' => ['terms' => ['children']],
            ],
            'bath' => [
                'builtin' => ['terms' => ['bath', 'built']],
                'free' => ['terms' => ['bath', 'free']],
                'show' => ['terms' => ['bath']],
            ],
        ],
    ],

    'cache_hours' => (int) env('ASSISTANT_CACHE_HOURS', 168),
];
