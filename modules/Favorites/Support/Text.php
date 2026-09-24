<?php

declare(strict_types=1);

namespace Modules\Favorites\Support;

final class Text
{
    public static function count(string $key, int $count, array $params = []): string
    {
        $params['n'] = (string) $count;

        return trans($key . ($count === 1 ? '_one' : '_many'), $params);
    }
}
