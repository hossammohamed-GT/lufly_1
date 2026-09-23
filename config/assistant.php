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

    'ai' => [
        'enabled' => (bool) env('ASSISTANT_AI', true),
        /* reading a photo needs a key that can see — switch it off to save quota */
        'vision' => (bool) env('ASSISTANT_VISION', true),
        /* empty = the model from config/ai.php */
        'model' => (string) env('ASSISTANT_AI_MODEL', ''),
        'max_terms' => 5,
    ],

    /* how long one answer is remembered (the wording may change, the catalogue not) */
    'cache_hours' => (int) env('ASSISTANT_CACHE_HOURS', 168),
];
