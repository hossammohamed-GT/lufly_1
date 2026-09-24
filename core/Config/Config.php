<?php

declare(strict_types=1);

namespace Core\Config;

use Core\Support\Arr;

final class Config
{
    private static string $path = '';

    private static array $items = [];

    private static array $loaded = [];

    public static function setPath(string $path): void
    {
        self::$path = rtrim($path, '/\\');
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        [$file] = explode('.', $key, 2);
        self::loadFile($file);

        return Arr::get(self::$items, $key, $default);
    }

    public static function set(string $key, mixed $value): void
    {
        [$file] = explode('.', $key, 2);
        self::loadFile($file);

        Arr::set(self::$items, $key, $value);
    }

    public static function all(string $file): array
    {
        self::loadFile($file);

        return self::$items[$file] ?? [];
    }

    private static function loadFile(string $file): void
    {
        if (isset(self::$loaded[$file])) {
            return;
        }
        self::$loaded[$file] = true;

        $path = self::$path . DIRECTORY_SEPARATOR . $file . '.php';
        if (is_file($path)) {
            self::$items[$file] = require $path;
        }
    }
}
