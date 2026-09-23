<?php

declare(strict_types=1);

/*
 * Bathroom planner.
 *
 * The visitor answers three quick questions (size, wet area, look) and gets a
 * complete plan: what to install, in which size, a scaled drawing and — if they
 * want it — a picture of the finished room.
 *
 * Everything in this file is the *fixed* knowledge: presets and item slots. It
 * is deliberately small and opinionated — a visitor should tap, not write, and
 * the assistant should never invent a fitting. The AI only writes the friendly
 * wording around these slots (see PlannerService), never the dimensions.
 */
return [
    'enabled' => (bool) env('PLANNER_ENABLED', true),

    /* room presets, in centimetres (width = the wall with the door, length = depth) */
    'sizes' => [
        'compact' => ['w' => 160, 'l' => 200],
        'standard' => ['w' => 200, 'l' => 250],
        'family' => ['w' => 250, 'l' => 300],
    ],
    'custom' => ['min' => 100, 'max' => 600],

    /* the three looks: one tap sets style + finish together */
    'looks' => [
        'modern-chrome' => ['style' => 'modern', 'finish' => 'chrome'],
        'modern-matte' => ['style' => 'modern', 'finish' => 'matte-black'],
        'classic-chrome' => ['style' => 'classic', 'finish' => 'chrome'],
        'minimal-steel' => ['style' => 'minimal', 'finish' => 'brushed-steel'],
    ],

    /* how many products from our own catalogue are offered per slot */
    'picks_per_slot' => 2,

    /*
     * Item slots. `when` decides whether the slot belongs in the plan:
     *   always            every room
     *   wet:shower|bath   the visitor chose that wet area
     *   area>=N           the room is at least N m²
     * `size` is the recommended size, `keywords` are used to find a match in
     * the catalogue (any locale), `category` narrows that search.
     */
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

    /* the AI writes the wording only */
    'ai' => [
        'enabled' => (bool) env('PLANNER_AI', true),
        'max_words' => 140,
    ],

    /* the optional picture of the finished room */
    'render' => [
        'enabled' => (bool) env('PLANNER_RENDER', true),
        /*
         * Image generation is not something a free Google account can do today
         * (every call comes back 429), so the plan shows a "coming soon" badge
         * instead of a button that would only fail. The day a billed key is in
         * AI_IMAGE_MODEL, PLANNER_RENDER_SOON=false brings the button back.
         */
        'soon' => (bool) env('PLANNER_RENDER_SOON', true),
        'daily_per_ip' => (int) env('PLANNER_RENDER_DAILY', 3),
        'references' => 2,
    ],

    /*
     * "Does this piece fit the plan I just made?" — the one question the chat
     * answers about a single product.
     *
     * The product travels as *text* (name, description, category): no images,
     * no catalogue sweep, and the model sees the plan outline it already wrote.
     * The verdict itself is computed here first; the AI only rephrases it.
     */
    'fit' => [
        'enabled' => (bool) env('PLANNER_FIT', true),
        'daily_per_ip' => (int) env('PLANNER_FIT_DAILY', 8),
        'max_words' => 80,
        /* how much of the product description may reach the model */
        'text_chars' => 600,
    ],

    /* the chat that floats over every storefront page */
    'chat' => [
        'enabled' => (bool) env('PLANNER_CHAT', true),
    ],

    /*
     * The planner is not offered yet: the chat's second tab says "coming soon"
     * and /planner answers with the same promise instead of the flow.
     *
     * Nothing is deleted — the whole flow, the drawing and the picture are
     * still here and still tested, and PLANNER_COMING_SOON=false puts the page
     * back exactly as it was.
     */
    'coming_soon' => (bool) env('PLANNER_COMING_SOON', true),

    /* where a visitor's plan goes when they ask us to source the pieces */
    'handoff' => [
        'email' => (string) env('PLANNER_HANDOFF_EMAIL', 'info@lufly.tr'),
        'phone' => (string) env('PLANNER_HANDOFF_PHONE', '+90 850 3040 817'),
        /* digits only, the way wa.me wants it */
        'whatsapp' => (string) env('PLANNER_WHATSAPP', '908503040817'),
    ],

    /* seconds of cache life for a plan (answers are deterministic, so a month) */
    'cache_hours' => (int) env('PLANNER_CACHE_HOURS', 720),
];
