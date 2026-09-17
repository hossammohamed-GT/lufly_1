<?php

declare(strict_types=1);

namespace Core\Foundation;

class FeatureFlag
{
    public static function enabled(string $feature, bool $default = false): bool
    {
        $features = (array) config('features', []);

        return (bool) ($features[$feature] ?? $default);
    }

    public static function all(): array
    {
        return (array) config('features', []);
    }
}
