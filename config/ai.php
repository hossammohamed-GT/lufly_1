<?php

declare(strict_types=1);

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
    'enabled' => (bool) env('AI_ENABLED', (bool) env('FEATURE_AI', true)),

    'endpoint' => rtrim((string) env('AI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta'), '/'),

    'keys' => array_values(array_unique(array_filter([
        (string) env('AI_KEYS', ''),
        (string) env('AI_KEY_1', ''),
        (string) env('AI_KEY_2', ''),
        (string) env('AI_KEY_3', ''),
        (string) env('AI_KEY_4', ''),
        (string) env('AI_KEY_5', ''),
    ], static fn (string $value): bool => trim($value) !== ''))),

    'model' => $model('AI_MODEL', 'gemini-2.5-flash') ?: 'gemini-2.5-flash',
    'models' => [
        $model('AI_MODEL_1'),
        $model('AI_MODEL_2'),
        $model('AI_MODEL_3'),
        $model('AI_MODEL_4'),
        $model('AI_MODEL_5'),
    ],
    'image_model' => $rawImageModel === ''
        ? ''
        : ($model('AI_IMAGE_MODEL', 'gemini-2.5-flash-image') ?: 'gemini-2.5-flash-image'),

    'timeout' => (int) env('AI_TIMEOUT', 45),
    'connect_timeout' => (int) env('AI_CONNECT_TIMEOUT', 10),
    'max_output_tokens' => (int) env('AI_MAX_OUTPUT_TOKENS', 2048),
    'temperature' => (float) env('AI_TEMPERATURE', 0.4),

    'retry' => [
        'attempts' => (int) env('AI_RETRY_ATTEMPTS', 3),
        'sleep_ms' => (int) env('AI_RETRY_SLEEP_MS', 400),
    ],

    'max_images_per_request' => (int) env('AI_MAX_IMAGES', 4),

    'daily_limit_per_ip' => (int) env('AI_DAILY_LIMIT_PER_IP', 15),

    'cache_hours' => (int) env('AI_CACHE_HOURS', 720),

    'log' => (bool) env('AI_LOG', true),
];
