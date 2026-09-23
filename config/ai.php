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
/*
 * A model name has to look like one. The .env loader already drops inline
 * comments, but a value with a space, a `#` or a newline in it (a comment
 * pasted onto the same line by hand, a copied line breaking in two) is not a
 * model and never reaches Google: it falls back to the default instead, and
 * `php cli ai:doctor` says which line it was.
 */
$rawImageModel = trim((string) env('AI_IMAGE_MODEL', 'gemini-2.5-flash-image'));

$model = static function (string $key, string $default = ''): string {
    $value = (string) env($key, $default);

    if (trim($value) === '') {
        return '';
    }

    $value = trim((string) preg_replace('/\s.*$/', '', trim($value)));

    return preg_match('/^[A-Za-z0-9._-]+$/', $value) === 1 ? $value : '';
};

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
    'model' => $model('AI_MODEL', 'gemini-2.5-flash') ?: 'gemini-2.5-flash',
    'models' => [
        $model('AI_MODEL_1'),
        $model('AI_MODEL_2'),
        $model('AI_MODEL_3'),
        $model('AI_MODEL_4'),
        $model('AI_MODEL_5'),
    ],
    /*
     * Model that may generate images (Nano Banana). `AI_IMAGE_MODEL=` (empty)
     * switches image output off — the planner then hides its picture button
     * instead of promising something the account cannot draw.
     */
    'image_model' => $rawImageModel === ''
        ? ''
        : ($model('AI_IMAGE_MODEL', 'gemini-2.5-flash-image') ?: 'gemini-2.5-flash-image'),

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
