<?php

declare(strict_types=1);

namespace Core\Logging;

final class Log
{
    private static array $loggers = [];

    public static function channel(string $channel): Logger
    {
        if (isset(self::$loggers[$channel])) {
            return self::$loggers[$channel];
        }

        $file = config("logging.channels.{$channel}", $channel . '.log');
        $path = str_contains((string) $file, DIRECTORY_SEPARATOR) || str_contains((string) $file, '/')
            ? (string) $file
            : rtrim((string) config('logging.path', 'storage/logs'), '/') . '/' . $file;

        if (!str_starts_with($path, '/') && !preg_match('/^[A-Za-z]:[\\\\\/]/', $path)) {
            $base = $GLOBALS['__app'] ?? null;
            $path = $base !== null ? $base->basePath($path) : $path;
        }

        return self::$loggers[$channel] = new Logger(
            $channel,
            $path,
            (string) config('logging.level', env('LOG_LEVEL', 'debug')),
        );
    }

    public static function debug(string $message, array $context = []): void
    {
        self::channel(self::defaultChannel())->debug($message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::channel(self::defaultChannel())->info($message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::channel(self::defaultChannel())->warning($message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::channel(self::defaultChannel())->error($message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::channel(self::defaultChannel())->critical($message, $context);
    }

    private static function defaultChannel(): string
    {
        return (string) config('logging.default', 'app');
    }
}
