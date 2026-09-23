<?php

declare(strict_types=1);

namespace Core\Console\Commands;

use App\Services\Ai\AiClient;
use Core\Console\Command;

/**
 * Checks the Gemini key pool: one tiny request per key, so a broken account is
 * found before a visitor finds it.
 *
 *   php cli ai:doctor              # text answer per key
 *   php cli ai:doctor --image      # also tries one image (Nano Banana)
 *   php cli ai:doctor --json       # also asks for a JSON answer
 *
 * Every key is reported with the model it uses, the latency and the exact
 * answer from Google (a 400 usually means the model name, a 403 the key).
 */
final class AiDoctorCommand extends Command
{
    protected string $name = 'ai:doctor';

    protected string $description = 'Test the configured Gemini keys/models with one tiny request each.';

    public function handle(array $args, array $options): int
    {
        $client = $this->app->get(AiClient::class);

        $this->line('');
        $this->line('LUFLY AI doctor');
        $this->line('---------------');
        $this->line('enabled      : ' . ((bool) config('ai.enabled', true) ? 'yes' : 'NO (AI_ENABLED=false)'));
        $this->line('endpoint     : ' . (string) config('ai.endpoint', ''));
        $this->line('keys         : ' . $client->keyCount());
        $this->line('text model   : ' . (string) config('ai.model', ''));
        $this->line('image model  : ' . ((string) config('ai.image_model', '') !== '' ? (string) config('ai.image_model') : 'disabled'));
        $this->line('timeout      : ' . (int) config('ai.timeout', 45) . 's');

        /* A value that cannot be a model name (a comment pasted onto the same
           line, a line that broke in two) never reaches Google: say which line
           it was instead of letting a 400 blame the key. */
        $this->warnAboutModelValues();

        $this->line('');

        if ($client->keyCount() === 0) {
            $this->error('No keys found. Add AI_KEY_1 … AI_KEY_5 (or AI_KEYS=…) to .env and run `php cli config:clear` if the app caches config.');

            return 1;
        }

        foreach ($client->keyLabels() as $label) {
            $this->line('key #' . $label['slot'] . ' ' . $label['fingerprint'] . '  ·  ' . $label['style'] . '  ·  model ' . $label['model']);
        }
        $this->line('');

        $working = 0;

        foreach ($client->keyLabels() as $label) {
            $started = microtime(true);
            $result = $this->callWithKey($client, $label['slot'], $label['model']);
            $ms = (int) round((microtime(true) - $started) * 1000);

            if ($result['ok']) {
                $working++;
                $this->success(sprintf('key #%d  ok   %dms  →  %s', $label['slot'], $ms, $this->shorten($result['text'])));
            } else {
                $this->error(sprintf('key #%d  FAIL %dms  →  %s', $label['slot'], $ms, $this->shorten((string) $result['error'])));
            }
        }

        if ((bool) ($options['json'] ?? false)) {
            $this->line('');
            $result = $client->json('Reply with a JSON object holding exactly two keys: "ok" set to true and "site" set to "lufly".');
            $this->line('json mode    : ' . ($result['ok'] && $result['data'] !== [] ? 'ok → ' . json_encode($result['data']) : 'FAILED → ' . $this->shorten((string) $result['error'])));
        }

        if ((bool) ($options['image'] ?? false)) {
            $this->line('');
            $started = microtime(true);
            $result = $client->image('A single matte white wall-hung toilet on a plain light grey studio background, product photo.');
            $quota = $this->looksLikeQuota((string) ($result['error'] ?? ''));
            $ms = (int) round((microtime(true) - $started) * 1000);

            if ($result['ok'] && $result['images'] !== []) {
                $bytes = strlen((string) base64_decode($result['images'][0]['data'], true));
                $file = storage_path('tmp/ai-doctor-image.png');
                @mkdir(dirname($file), 0775, true);
                @file_put_contents($file, (string) base64_decode($result['images'][0]['data'], true));
                $this->success(sprintf('image model ok   %dms  →  %s (%d KB) saved to %s', $ms, $result['images'][0]['mime'], (int) round($bytes / 1024), $file));
            } else {
                $this->error('image model FAIL → ' . $this->shorten((string) ($result['error'] ?: 'no image returned')));

                if ($quota) {
                    $this->line('   the key itself is fine (Google answered) — the image quota is not:');
                    $this->line('   image generation is the first thing the free tier withholds. Options:');
                    $this->line('     · leave AI_IMAGE_MODEL= empty — the planner then hides its picture button');
                    $this->line('       and the plan, the drawing and the products keep working;');
                    $this->line('     · or point AI_IMAGE_MODEL at an account with billing enabled.');
                }
            }
        }

        $this->line('');
        $this->line($working . ' of ' . $client->keyCount() . ' key(s) answered.');

        if ($working === 0) {
            $this->error('Nothing worked. Check the key value, the model name and that the server can reach generativelanguage.googleapis.com.');

            return 1;
        }

        return 0;
    }

    /**
     * Is a model value in .env something that could never be a model name?
     * Returns how many lines were flagged.
     */
    private function warnAboutModelValues(): int
    {
        $keys = ['AI_MODEL', 'AI_MODEL_1', 'AI_MODEL_2', 'AI_MODEL_3', 'AI_MODEL_4', 'AI_MODEL_5', 'AI_IMAGE_MODEL'];
        $flagged = 0;

        foreach ($keys as $key) {
            $raw = trim((string) env($key, ''));

            /* empty is a legitimate answer: "use the default" / "switched off" */
            if ($raw === '' || preg_match('/^[A-Za-z0-9._-]+$/', $raw) === 1) {
                continue;
            }

            $flagged++;
            $this->error($key . ' in .env is not a model name: "' . $this->shorten($raw) . '"');
            $this->line('   → a comment must live on its own line (`# …`), not at the end of a value line.');
            $this->line('   → the value was ignored and the default model is used instead.');
        }

        return $flagged;
    }

    /** Google's 429: the request was understood, the quota is what ran out. */
    private function looksLikeQuota(string $error): bool
    {
        return str_contains($error, '429') || stripos($error, 'quota') !== false;
    }

    /**
     * Ask one specific key, bypassing the rotation (that is the point of the
     * doctor: every account is tested on its own).
     *
     * @return array{ok: bool, text: string, error: ?string}
     */
    private function callWithKey(AiClient $client, int $slot, string $model): array
    {
        $reflection = new \ReflectionClass($client);
        $keys = $reflection->getProperty('keys');
        $keys->setAccessible(true);
        $values = (array) $keys->getValue($client);

        $key = (string) ($values[$slot - 1] ?? '');
        if ($key === '') {
            return ['ok' => false, 'text' => '', 'error' => 'missing key'];
        }

        $post = $reflection->getMethod('post');
        $post->setAccessible(true);

        $payload = [
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' => 'Reply with the single word: pong']],
            ]],
            'generationConfig' => ['temperature' => 0, 'maxOutputTokens' => 32],
        ];

        $response = $post->invoke($client, $model, $payload, $key);

        if (($response['status'] ?? 0) >= 200 && ($response['status'] ?? 0) < 300) {
            $parts = (array) ($response['body']['candidates'][0]['content']['parts'] ?? []);
            $text = '';
            foreach ($parts as $part) {
                $text .= (string) ($part['text'] ?? '');
            }

            return ['ok' => true, 'text' => trim($text) !== '' ? trim($text) : '(empty answer)', 'error' => null];
        }

        $message = (string) ($response['body']['error']['message'] ?? $response['raw'] ?? 'unknown error');

        return ['ok' => false, 'text' => '', 'error' => 'HTTP ' . ($response['status'] ?? 0) . ' — ' . $message];
    }

    private function shorten(string $text): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);

        return mb_strlen($text) > 120 ? mb_substr($text, 0, 117) . '…' : $text;
    }
}
