<?php

declare(strict_types=1);

namespace App\Models;

use Core\Database\Model;

class Category extends Model
{
    protected static string $table = 'categories';

    protected static array $casts = [
        'seo' => 'json',
        'sort_order' => 'int',
    ];

    /** @return array<string, array<string, mixed>> translations keyed by locale */
    public function translations(): array
    {
        $rows = static::db()->connection()->select(
            'SELECT * FROM category_translations WHERE category_id = ?',
            [$this->getKey()],
        );

        $out = [];
        foreach ($rows as $row) {
            $out[$row['locale']] = $row;
        }

        return $out;
    }

    public function translate(string $locale): ?array
    {
        $translations = $this->translations();
        $fallback = (string) config('localization.fallback', 'en');

        return $translations[$locale] ?? $translations[$fallback] ?? null;
    }
}
