<?php

declare(strict_types=1);

/*
 * The finder — "I am looking for something like this".
 *
 * The visitor writes an item in their own words (any language) or drops a
 * photo, and gets back the pieces from *our own catalogue* that match: a bank
 * of cards with the picture, the code and a link to the product page.
 *
 * Two rules keep it cheap:
 *
 *  1. the catalogue is never sent to the model. The words are matched against
 *     the products locally (see CatalogFinder); the model is only asked, when
 *     the local pass found nothing good, to turn a sentence or a photo into a
 *     handful of search words (a JSON of at most five of them).
 *  2. nothing is asked of the model while the local answer is already good.
 *
 * Every number here is overridable in .env — see .env.example.
 */
return [
    'enabled' => (bool) env('ASSISTANT_ENABLED', true),

    'chat' => [
        'enabled' => (bool) env('ASSISTANT_CHAT', true),
        /* how much of the visitor's own words are kept (a paragraph, not an essay) */
        'max_chars' => (int) env('ASSISTANT_MAX_CHARS', 600),
        /* answers per visitor per day, on top of the AI guard */
        'daily_per_ip' => (int) env('ASSISTANT_DAILY', 20),
    ],

    /* the catalogue search itself: how many cards, how many words, what counts
       as a good hit (`good_score` = answer locally, don't wake the model) */
    'find' => [
        'limit' => (int) env('ASSISTANT_LIMIT', 6),
        'terms' => (int) env('ASSISTANT_TERMS', 8),
        'scanned' => 24,
        'good_score' => (int) env('ASSISTANT_GOOD_SCORE', 24),
        'min_score' => 3,
    ],

    /* the photo the visitor may attach instead of describing the piece */
    'photo' => [
        'enabled' => (bool) env('ASSISTANT_PHOTO', true),
        /* what we accept from the browser (the script shrinks it first) */
        'max_kb' => (int) env('ASSISTANT_PHOTO_KB', 4096),
        'min_side' => 160,
        'types' => ['image/jpeg', 'image/png', 'image/webp'],
        /* the folder is inside the public root so the link in the e-mail opens */
        'folder' => 'uploads/assistant',
    ],

    /*
     * The visitor's address and the mail the shop gets.
     *
     * `email` is where "this person sent this photo" is delivered. `default_email`
     * is what the customers table stores when the visitor skipped the field —
     * change both to the mailbox the team actually reads.
     */
    'lead' => [
        'email' => (string) env('ASSISTANT_LEAD_MAIL', 'hossam545mohamed@gmail.com'),
        'default_email' => (string) env('ASSISTANT_LEAD_DEFAULT', 'hossam545mohamed@gmail.com'),
        /* ask for the address before the first answer (never twice) */
        'ask_email' => (bool) env('ASSISTANT_ASK_EMAIL', true),
    ],

    /*
     * The version of the chat itself.
     *
     * It is printed in the widget's markup (right-click the page, "view source",
     * search for assistant-build) and by `php cli ai:doctor`, so a shop can tell
     * in one look whether the copy it is serving is the one that was just
     * deployed — instead of wondering why a fix does not show.
     */
    'build' => (string) env('ASSISTANT_BUILD', '2026-09-24.5'),

    'ai' => [
        'enabled' => (bool) env('ASSISTANT_AI', true),
        /* reading a photo needs a key that can see — switch it off to save quota */
        'vision' => (bool) env('ASSISTANT_VISION', true),
        /* empty = the model from config/ai.php */
        'model' => (string) env('ASSISTANT_AI_MODEL', ''),
        'max_terms' => 5,
    ],

    /*
     * The guided conversation.
     *
     * The chat is not a search box: a visitor who says hello, or who asks a
     * question without naming a piece, gets a short answer and a small set of
     * choices — never six products out of nowhere. Each topic below is one
     * question the chat can ask, and each option is one answer the visitor can
     * tap, carrying the catalogue words that option means.
     *
     * The words used to *recognise* a topic live in the Conversation service
     * (they are language data, not settings); the sentences the visitor reads
     * live in resources/lang/<locale>/assistant.php.
     */
    'guide' => [
        'enabled' => (bool) env('ASSISTANT_GUIDE', true),

        /* the small set of things a bathroom most often needs */
        'topics' => [
            'basin' => ['category' => 'bathroom-ceramics', 'terms' => ['washbasin']],
            'toilet' => ['category' => 'bathroom-ceramics', 'terms' => ['wc']],
            'shower' => ['category' => 'shower-sets', 'terms' => ['shower']],
            'tap' => ['category' => '', 'terms' => ['mixer']],
            'access' => ['category' => 'accessible-range', 'terms' => ['accessible']],
            'kids' => ['category' => 'kids', 'terms' => ['children']],
            'bath' => ['category' => '', 'terms' => ['bath']],
        ],

        /* what each answer means, as catalogue words */
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

    /* how long one answer is remembered (the wording may change, the catalogue not) */
    'cache_hours' => (int) env('ASSISTANT_CACHE_HOURS', 168),
];
