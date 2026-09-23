<?php

declare(strict_types=1);

/*
 * AI (Google Gemini) — the multi-key pool behind the assistant, the bathroom
 * planner and (later) the product-image search.
 *
 * Five free AI Studio accounts can be rotated: every key is a separate Google
 * project with its own daily quota, so the pool multiplies the free limit
 * instead of sharing it. The client never guesses which key to use — it walks
 * the ring on a 429 / quota error and remembers the working one.
 *
 * Nothing here is billed by this application: if the pool runs dry the AI
 * features answer "busy", and the storefront keeps working.
 */
return [
    /* master switch — also available as FEATURE_AI in .env */
    'enabled' => (bool) env('AI_ENABLED', (bool) env('FEATURE_AI', true)),

    /* Google AI Studio endpoint (override only for a proxy or a mirror) */
    'endpoint' => rtrim((string) env('AI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta'), '/'),

    /*
     * API keys. Both shapes are supported:
     *   AI_KEY_1=…  AI_KEY_2=…  …   (one per account, easiest to read)
     *   AI_KEYS=…,…,…               (one comma separated line)
     * The two forms are merged, duplicates dropped, order kept.
     */
    'keys' => array_values(array_unique(array_filter([
        (string) env('AI_KEYS', ''),
        (string) env('AI_KEY_1', ''),
        (string) env('AI_KEY_2', ''),
        (string) env('AI_KEY_3', ''),
        (string) env('AI_KEY_4', ''),
        (string) env('AI_KEY_5', ''),
    ], static fn (string $value): bool => trim($value) !== ''))),

    /*
     * Models. AI_MODEL is the one every key uses; AI_MODEL_N overrides it for
     * that account, so a weaker key can serve the cheap jobs (labels, keywords)
     * while the main key keeps the reasoning ones.
     */
    'model' => (string) env('AI_MODEL', 'gemini-2.5-flash'),
    'models' => [
        (string) env('AI_MODEL_1', ''),
        (string) env('AI_MODEL_2', ''),
        (string) env('AI_MODEL_3', ''),
        (string) env('AI_MODEL_4', ''),
        (string) env('AI_MODEL_5', ''),
    ],
    /* model that may generate images (Nano Banana). '' disables image output. */
    'image_model' => (string) env('AI_IMAGE_MODEL', 'gemini-2.5-flash-image'),

    /* request shape */
    'timeout' => (int) env('AI_TIMEOUT', 45),
    'connect_timeout' => (int) env('AI_CONNECT_TIMEOUT', 10),
    'max_output_tokens' => (int) env('AI_MAX_OUTPUT_TOKENS', 2048),
    'temperature' => (float) env('AI_TEMPERATURE', 0.4),

    /* keep the key ring from hammering a dead account on every request */
    'retry' => [
        'attempts' => (int) env('AI_RETRY_ATTEMPTS', 3),
        'sleep_ms' => (int) env('AI_RETRY_SLEEP_MS', 400),
    ],

    /* one request may carry how many images (the planner sends the room + a pick) */
    'max_images_per_request' => (int) env('AI_MAX_IMAGES', 4),

    /* per-visitor guard rails (the endpoints are public) */
    'daily_limit_per_ip' => (int) env('AI_DAILY_LIMIT_PER_IP', 15),

    /* cached answers: how long a stored result stays valid, in hours */
    'cache_hours' => (int) env('AI_CACHE_HOURS', 720),

    /* write every call (key used, model, tokens, ms) to storage/logs/ai.log */
    'log' => (bool) env('AI_LOG', true),
];
