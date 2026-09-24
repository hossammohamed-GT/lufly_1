<?php

declare(strict_types=1);

return [
    'enabled' => (bool) env('PLANNER_ENABLED', true),

    'sizes' => [
        'compact' => ['w' => 160, 'l' => 200],
        'standard' => ['w' => 200, 'l' => 250],
        'family' => ['w' => 250, 'l' => 300],
    ],
    'custom' => ['min' => 100, 'max' => 600],

    'looks' => [
        'modern-chrome' => ['style' => 'modern', 'finish' => 'chrome'],
        'modern-matte' => ['style' => 'modern', 'finish' => 'matte-black'],
        'classic-chrome' => ['style' => 'classic', 'finish' => 'chrome'],
        'minimal-steel' => ['style' => 'minimal', 'finish' => 'brushed-steel'],
    ],

    'picks_per_slot' => 2,

    'slots' => [
        'toilet' => [
            'when' => 'always',
            'size' => '370 × 600 mm',
            'category' => 'bathroom-ceramics',
            'keywords' => ['toilet', 'wc', 'mísa', 'misa', 'klozet'],
        ],
        'basin' => [
            'when' => 'always',
            'size' => '550 × 450 mm',
            'category' => 'bathroom-ceramics',
            'keywords' => ['basin', 'umyvadlo', 'lavabo', 'washbasin'],
        ],
        'basin_mixer' => [
            'when' => 'always',
            'size' => 'reach 150 mm',
            'category' => 'washbasin-mixers',
            'keywords' => ['mixer', 'batérie', 'baterie', 'armatur'],
        ],
        'shower' => [
            'when' => 'wet:shower',
            'size' => '800 × 800 mm tray',
            'category' => 'shower-sets',
            'keywords' => ['shower', 'sprch', 'duş', 'dus'],
        ],
        'bathtub' => [
            'when' => 'wet:bath',
            'size' => '1700 × 700 mm',
            'category' => 'bathroom-ceramics',
            'keywords' => ['bath', 'bathtub', 'vana', 'wanne'],
        ],
        'towel_bar' => [
            'when' => 'always',
            'size' => '600 mm',
            'category' => 'bathroom-ceramics',
            'keywords' => ['towel', 'ručník', 'havlu', 'bar'],
        ],
        'paper_holder' => [
            'when' => 'always',
            'size' => '150 × 130 mm',
            'category' => 'bathroom-ceramics',
            'keywords' => ['paper', 'papíru', 'tuvalet', 'holder'],
        ],
        'mirror' => [
            'when' => 'area>=3.5',
            'size' => '600 × 800 mm',
            'category' => '',
            'keywords' => ['mirror', 'zrcadlo', 'ayna'],
        ],
        'second_basin' => [
            'when' => 'area>=6',
            'size' => '550 × 450 mm',
            'category' => 'bathroom-ceramics',
            'keywords' => ['basin', 'umyvadlo', 'washbasin'],
        ],
    ],

    'ai' => [
        'enabled' => (bool) env('PLANNER_AI', true),
        'max_words' => 140,
    ],

    'render' => [
        'enabled' => (bool) env('PLANNER_RENDER', true),
        'soon' => (bool) env('PLANNER_RENDER_SOON', true),
        'daily_per_ip' => (int) env('PLANNER_RENDER_DAILY', 3),
        'references' => 2,
    ],

    'fit' => [
        'enabled' => (bool) env('PLANNER_FIT', true),
        'daily_per_ip' => (int) env('PLANNER_FIT_DAILY', 8),
        'max_words' => 80,
        'text_chars' => 600,
    ],

    'chat' => [
        'enabled' => (bool) env('PLANNER_CHAT', true),
    ],

    'coming_soon' => (bool) env('PLANNER_COMING_SOON', true),

    'handoff' => [
        'email' => (string) env('PLANNER_HANDOFF_EMAIL', 'info@lufly.tr'),
        'phone' => (string) env('PLANNER_HANDOFF_PHONE', '+90 850 3040 817'),
        'whatsapp' => (string) env('PLANNER_WHATSAPP', '908503040817'),
    ],

    'cache_hours' => (int) env('PLANNER_CACHE_HOURS', 720),
];
