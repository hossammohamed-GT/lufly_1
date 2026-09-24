<?php

declare(strict_types=1);

namespace Core\Config;

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

    public static function parseValue(string $raw): string|bool|null
    {
        $value = trim($raw);

        if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
            $quote = $value[0];
            $closing = strpos($value, $quote, 1);

            return $closing === false ? substr($value, 1) : substr($value, 1, $closing - 1);
        }

        if (preg_match('/\s[#;]/', $raw, $match, PREG_OFFSET_CAPTURE) === 1) {
            $value = trim(substr($raw, 0, (int) $match[0][1]));
        }

        return match (strtolower($value)) {
            'true' => true,
            'false' => false,
            'null' => null,
            default => $value,
        };
    }
}
