<?php

declare(strict_types=1);

namespace App\Services\Ai;

use Core\Logging\Log;
use Throwable;

/**
 * Google Gemini client with a rotating key pool.
 *
 * One HTTP call does everything: prompt, conversation, image input and image
 * output all travel in `contents` (a text part plus optional inline_data
 * parts), exactly as the Gemini REST API expects.
 *
 * Two details matter and are easy to get wrong:
 *
 *   1. The key travels in the `x-goog-api-key` header, never in `?key=`. The
 *      keys AI Studio issues today start with `AQ.` and are rejected by the
 *      query-parameter route (Google answers 404), while the header works.
 *   2. A key pool is walked, not chosen: a 429 (quota) or a 5xx moves to the
 *      next key, so five free accounts behave like five times the daily limit.
 *
 * Nothing here throws. Every call returns a result object, failures included,
 * so an AI outage can never take a storefront page down with it.
 */
class AiClient
{
    /** @var array<int, string> keys in ring order */
    private array $keys;

    /** @var array<int, string> per-key model override */
    private array $models;

    private int $cursor = 0;

    public function __construct()
    {
        $this->keys = array_values(array_filter(
            (array) config('ai.keys', []),
            static fn (mixed $key): bool => trim((string) $key) !== '',
        ));

        $this->models = array_values((array) config('ai.models', []));
    }

    public function enabled(): bool
    {
        /* FEATURE_AI=false in .env switches every AI feature off, AI_ENABLED is
           the module-level switch, and a pool without keys is simply off. */
        return feature('ai', true)
            && (bool) config('ai.enabled', true)
            && $this->keys !== [];
    }

    public function keyCount(): int
    {
        return count($this->keys);
    }

    /** Masked key list, for diagnostics (`php cli ai:doctor`). */
    public function keyLabels(): array
    {
        return array_map(
            static fn (string $key, int $index): array => [
                'slot' => $index + 1,
                'model' => $index < count((array) config('ai.models', [])) && (string) config('ai.models')[$index] !== ''
                    ? (string) config('ai.models')[$index]
                    : (string) config('ai.model', 'gemini-2.5-flash'),
                'fingerprint' => substr($key, 0, 6) . '…' . substr($key, -4),
                'style' => str_starts_with($key, 'AQ.') ? 'aq (header auth)' : 'standard',
            ],
            $this->keys,
            array_keys($this->keys),
        );
    }

    /* ------------------------------------------------------------------ API */

    /**
     * Ask a model to generate content.
     *
     * @param string|array<int, mixed> $contents  a prompt, or a full `contents` array
     * @param array<string, mixed> $options       model, system, json, images, temperature, …
     * @return array{ok: bool, text: string, data: array<string, mixed>, images: array<int, array{mime: string, data: string}>, raw: array<string, mixed>, error: ?string, key: int, model: string}
     */
    public function generate(string|array $contents, array $options = []): array
    {
        $model = (string) ($options['model'] ?? config('ai.model', 'gemini-2.5-flash'));
        $jsonMode = (bool) ($options['json'] ?? false);
        $images = (array) ($options['images'] ?? []);
        $attempts = min(max(1, (int) config('ai.retry.attempts', 3)), max(1, $this->keyCount()));

        if ($this->keys === []) {
            return $this->failure('No AI key configured (AI_KEY_1 … AI_KEY_5).', $model);
        }

        $parts = $this->parts($contents, $images, (string) ($options['system'] ?? ''));
        $payload = [
            'contents' => [['role' => 'user', 'parts' => $parts]],
            'generationConfig' => $this->generationConfig($jsonMode, $options, $model),
        ];

        $lastError = null;

        for ($attempt = 0; $attempt < $attempts; $attempt++) {
            $slot = ($this->cursor + $attempt) % $this->keyCount();
            $key = $this->keys[$slot];
            $model = $this->modelFor($slot, (string) ($options['model'] ?? ''));

            $started = microtime(true);
            $response = $this->post($model, $payload, $key);
            $elapsed = (int) round((microtime(true) - $started) * 1000);

            if ($response['status'] >= 200 && $response['status'] < 300) {
                $this->cursor = $slot; /* remember the key that answered */

                return $this->success($response['body'], $model, $slot, $elapsed);
            }

            $lastError = $this->describeError($response);

            /* a dead key is skipped for good; a throttled one only for now */
            if ($response['status'] === 429 || $response['status'] >= 500) {
                $this->sleep();
                continue;
            }

            break;
        }

        $this->log('warning', 'ai.request_failed', [
            'model' => $model,
            'keys' => $this->keyCount(),
            'error' => $lastError,
        ]);

        return $this->failure((string) $lastError, $model);
    }

    /** Convenience: ask for a small JSON object and get it decoded. */
    public function json(string $prompt, array $options = []): array
    {
        return $this->generate($prompt, $options + ['json' => true]);
    }

    /**
     * Ask for an edited / generated image (Nano Banana).
     *
     * @param string|array<int, mixed> $contents
     * @param array<int, array{mime: string, data: string}> $images
     * @return array{ok: bool, images: array<int, array{mime: string, data: string}>, text: string, error: ?string, model: string}
     */
    public function image(string|array $contents, array $images = [], array $options = []): array
    {
        $model = (string) ($options['model'] ?? config('ai.image_model', ''));

        if ($model === '') {
            return ['ok' => false, 'images' => [], 'text' => '', 'error' => 'Image model disabled (AI_IMAGE_MODEL empty).', 'model' => ''];
        }

        $result = $this->generate($contents, $options + ['model' => $model, 'images' => $images]);

        return [
            'ok' => $result['ok'] && $result['images'] !== [],
            'images' => $result['images'],
            'text' => $result['text'],
            'error' => $result['error'],
            'model' => $result['model'],
        ];
    }

    /* -------------------------------------------------------------- internals */

    /**
     * Build the `parts` array: a text part, the inline images, then the system
     * prompt as a leading text part (Gemini has no separate system field in
     * v1beta's generateContent, so it is folded into the first text part).
     *
     * @param array<int, array{mime: string, data: string}> $images
     * @return array<int, array<string, mixed>>
     */
    private function parts(string|array $contents, array $images, string $system): array
    {
        if (is_array($contents)) {
            return $contents;
        }

        $limit = max(0, (int) config('ai.max_images_per_request', 4));
        $parts = [];

        if ($system !== '') {
            $parts[] = ['text' => $system . "\n\n" . $contents];
        } else {
            $parts[] = ['text' => $contents];
        }

        foreach (array_slice($images, 0, $limit === 0 ? 0 : $limit) as $image) {
            $mime = (string) ($image['mime'] ?? 'image/jpeg');
            $data = (string) ($image['data'] ?? '');
            if ($data === '') {
                continue;
            }

            $parts[] = [
                'inline_data' => [
                    'mime_type' => $mime,
                    'data' => $data,
                ],
            ];
        }

        return $parts;
    }

    /** @param array<string, mixed> $options */
    private function generationConfig(bool $jsonMode, array $options, string $model): array
    {
        $config = [
            'temperature' => (float) ($options['temperature'] ?? config('ai.temperature', 0.4)),
            'maxOutputTokens' => (int) ($options['max_tokens'] ?? config('ai.max_output_tokens', 2048)),
        ];

        if ($jsonMode) {
            $config['responseMimeType'] = 'application/json';
        }

        /* image models answer with an image part, never with text alone */
        if (str_contains($model, 'image') || (bool) ($options['with_image'] ?? false)) {
            $config['responseModalities'] = ['TEXT', 'IMAGE'];
        }

        foreach (['topP' => 'top_p', 'topK' => 'top_k'] as $target => $source) {
            if (isset($options[$source])) {
                $config[$target] = $options[$source];
            }
        }

        return $config;
    }

    private function modelFor(int $slot, string $requested): string
    {
        if ($requested !== '') {
            return $requested;
        }

        $override = (string) ($this->models[$slot] ?? '');
        if ($override !== '') {
            return $override;
        }

        return (string) config('ai.model', 'gemini-2.5-flash');
    }

    /**
     * One POST to the model endpoint. Returns status + decoded body.
     *
     * @param array<string, mixed> $payload
     * @return array{status: int, body: array<string, mixed>, raw: string}
     */
    private function post(string $model, array $payload, string $key): array
    {
        $endpoint = rtrim((string) config('ai.endpoint', 'https://generativelanguage.googleapis.com/v1beta'), '/');
        $url = $endpoint . '/models/' . rawurlencode($model) . ':generateContent';

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return ['status' => 0, 'body' => [], 'raw' => 'payload could not be encoded'];
        }

        /* Not every host has the curl extension enabled (a lot of shared
           cPanel accounts do not), so the call falls back to a plain stream. */
        if (!function_exists('curl_init')) {
            return $this->postViaStream($url, $json, $key);
        }

        $handle = curl_init($url);
        if ($handle === false) {
            return $this->postViaStream($url, $json, $key);
        }

        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                /* the only auth route the new AQ. keys accept */
                'x-goog-api-key: ' . $key,
            ],
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_TIMEOUT => max(5, (int) config('ai.timeout', 45)),
            CURLOPT_CONNECTTIMEOUT => max(3, (int) config('ai.connect_timeout', 10)),
        ]);

        try {
            $raw = curl_exec($handle);
            $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
            $curlError = curl_error($handle);
        } catch (Throwable $e) {
            curl_close($handle);

            return ['status' => 0, 'body' => [], 'raw' => $e->getMessage()];
        } finally {
            if (is_resource($handle) || $handle instanceof \CurlHandle) {
                curl_close($handle);
            }
        }

        if (!is_string($raw)) {
            return ['status' => $status, 'body' => [], 'raw' => $curlError !== '' ? $curlError : 'no response'];
        }

        $decoded = json_decode($raw, true);

        return [
            'status' => $status,
            'body' => is_array($decoded) ? $decoded : [],
            'raw' => $raw,
        ];
    }

    /**
     * Same POST without the curl extension (allow_url_fopen hosts).
     *
     * @return array{status: int, body: array<string, mixed>, raw: string}
     */
    private function postViaStream(string $url, string $json, string $key): array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n"
                    . 'x-goog-api-key: ' . $key . "\r\n"
                    . "Accept: application/json\r\n",
                'content' => $json,
                'timeout' => max(5, (int) config('ai.timeout', 45)),
                'ignore_errors' => true, /* keep the body of a 4xx/5xx */
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $raw = @file_get_contents($url, false, $context);
        $status = 0;
        foreach ((array) ($http_response_header ?? []) as $header) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', (string) $header, $m) === 1) {
                $status = (int) $m[1];
            }
        }

        if (!is_string($raw)) {
            return ['status' => $status, 'body' => [], 'raw' => 'stream request failed (allow_url_fopen / TLS)'];
        }

        $decoded = json_decode($raw, true);

        return ['status' => $status, 'body' => is_array($decoded) ? $decoded : [], 'raw' => $raw];
    }

    /**
     * @param array<string, mixed> $body
     * @return array{ok: bool, text: string, data: array<string, mixed>, images: array<int, array{mime: string, data: string}>, raw: array<string, mixed>, error: ?string, key: int, model: string}
     */
    private function success(array $body, string $model, int $slot, int $ms): array
    {
        $candidate = (array) ($body['candidates'][0] ?? []);
        $parts = (array) ($candidate['content']['parts'] ?? []);
        $text = '';
        $images = [];

        foreach ($parts as $part) {
            if (isset($part['text'])) {
                $text .= (string) $part['text'];
            }

            $inline = $part['inlineData'] ?? $part['inline_data'] ?? null;
            if (is_array($inline) && isset($inline['data'])) {
                $images[] = [
                    'mime' => (string) ($inline['mimeType'] ?? $inline['mime_type'] ?? 'image/png'),
                    'data' => (string) $inline['data'],
                ];
            }
        }

        $usage = (array) ($body['usageMetadata'] ?? []);
        $this->log('info', 'ai.request', [
            'model' => $model,
            'key' => $slot + 1,
            'ms' => $ms,
            'prompt_tokens' => (int) ($usage['promptTokenCount'] ?? 0),
            'output_tokens' => (int) ($usage['candidatesTokenCount'] ?? 0),
            'images' => count($images),
            'chars' => strlen($text),
        ]);

        return [
            'ok' => true,
            'text' => trim($text),
            'data' => $this->decodeJson($text),
            'images' => $images,
            'raw' => $body,
            'error' => null,
            'key' => $slot + 1,
            'model' => $model,
        ];
    }

    /** @return array{ok: bool, text: string, data: array<string, mixed>, images: array<int, array{mime: string, data: string}>, raw: array<string, mixed>, error: ?string, key: int, model: string} */
    private function failure(string $error, string $model): array
    {
        return [
            'ok' => false,
            'text' => '',
            'data' => [],
            'images' => [],
            'raw' => [],
            'error' => $error,
            'key' => 0,
            'model' => $model,
        ];
    }

    /** @return array<string, mixed> */
    private function decodeJson(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        /* models occasionally wrap JSON in a fenced block */
        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```[a-zA-Z]*\s*|\s*```$/', '', $text) ?? $text;
        }

        $decoded = json_decode($text, true);

        return is_array($decoded) ? $decoded : [];
    }

    /** @param array{status: int, body: array<string, mixed>, raw: string} $response */
    private function describeError(array $response): string
    {
        $message = (string) ($response['body']['error']['message'] ?? '');

        return match (true) {
            $response['status'] === 0 => 'Network error: ' . mb_substr($response['raw'], 0, 180),
            $message !== '' => 'HTTP ' . $response['status'] . ': ' . $message,
            default => 'HTTP ' . $response['status'] . ': ' . mb_substr($response['raw'], 0, 180),
        };
    }

    private function sleep(): void
    {
        $ms = max(0, (int) config('ai.retry.sleep_ms', 400));
        if ($ms > 0) {
            usleep($ms * 1000);
        }
    }

    /** @param array<string, mixed> $context */
    private function log(string $level, string $message, array $context): void
    {
        if (!(bool) config('ai.log', true)) {
            return;
        }

        Log::channel('ai')->log($level, $message, $context);
    }
}
