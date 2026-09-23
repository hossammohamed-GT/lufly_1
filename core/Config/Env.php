<?php

declare(strict_types=1);

namespace Core\Config;

/**
 * Minimal .env loader. Values are exposed through env() and $_ENV.
 *
 * Two things that bite in the real world are handled here:
 *
 *   * a `#` after whitespace starts a comment, so a line like
 *     `MAIL_PORT=587   # 587 = tls` means 587 — and a `#` inside a value
 *     (`MAIL_PASSWORD=pa#ss`) stays exactly where it is;
 *   * a quoted value keeps everything between its own quotes, comment or not,
 *     so `MAIL_FROM_NAME="LUFLY #1"` is `LUFLY #1`.
 */
final class Env
{
    public static function load(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);

            /* `export KEY=value` is what shells and some panels write */
            if (str_starts_with($line, 'export ')) {
                $line = trim(substr($line, 7));
            }

            if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, ';') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = self::parseValue($value);

            if (getenv($key) !== false && !isset($_ENV[$key])) {
                continue;
            }

            $_ENV[$key] = $value;
            putenv($key . '=' . (is_scalar($value) ? (string) $value : ''));
        }
    }

    /**
     * The text after `=`, exactly as it was written — the untrimmed form matters,
     * because `KEY=   # note` is a comment while `KEY=pa#ss` is a password.
     */
    public static function parseValue(string $raw): string|bool|null
    {
        $value = trim($raw);

        /* a quoted value runs to its closing quote and keeps whatever it holds */
        if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
            $quote = $value[0];
            $closing = strpos($value, $quote, 1);

            return $closing === false ? substr($value, 1) : substr($value, 1, $closing - 1);
        }

        /* an inline comment starts at a gap followed by # or ; — so a comment on
           the same line never becomes the value (that is what turned
           `AI_MODEL_1=  # …` into a model name Google rejected with a 400) */
        if (preg_match('/\s[#;]/', $raw, $match, PREG_OFFSET_CAPTURE) === 1) {
            $value = trim(substr($raw, 0, (int) $match[0][1]));
        }

        return match (strtolower($value)) {
            'true' => true,
            'false' => false,
            /* the word `null` is null; an empty value is an empty string, so
               `AI_IMAGE_MODEL=` can mean "switched off" instead of "default" */
            'null' => null,
            default => $value,
        };
    }
}
