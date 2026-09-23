<?php

declare(strict_types=1);

namespace Modules\Favorites\Support;

/**
 * Plural-aware wording for the saved-list feature.
 *
 * Every translation string that mentions a count ships two variants —
 * "…_one" and "…_many" — so a list holding a single product reads
 * "1 product" and not "1 products" (and the Turkish/Czech files can use
 * whatever form their grammar needs).
 */
final class Text
{
    /**
     * Resolve ":key_one" / ":key_many" for the given count, passing the count
     * through as the ":n" placeholder.
     *
     * @param array<string, string|int> $params
     */
    public static function count(string $key, int $count, array $params = []): string
    {
        $params['n'] = (string) $count;

        return trans($key . ($count === 1 ? '_one' : '_many'), $params);
    }
}
