<?php

declare(strict_types=1);

namespace Core\Foundation;

final class Autoloader
{
    private static array $prefixes = [];

    public static function register(array $prefixes): void
    {
        foreach ($prefixes as $prefix => $dir) {
            self::$prefixes[$prefix] = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR;
        }
        spl_autoload_register([self::class, 'load']);
    }

    public static function load(string $class): bool
    {
        foreach (self::$prefixes as $prefix => $baseDir) {
            if (str_starts_with($class, $prefix)) {
                $relative = substr($class, strlen($prefix));
                $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
                if (is_file($file)) {
                    require $file;
                    return true;
                }

                $parts = explode('\\', $relative);
                if (count($parts) > 1) {
                    $altParts = $parts;
                    $altParts[0] = strtolower($altParts[0]);
                    $altFile = $baseDir . implode(DIRECTORY_SEPARATOR, $altParts) . '.php';
                    if (is_file($altFile)) {
                        require $altFile;
                        return true;
                    }
                }
            }
        }
        return false;
    }
}
