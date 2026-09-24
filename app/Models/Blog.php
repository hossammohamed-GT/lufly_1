<?php

declare(strict_types=1);

namespace App\Models;

use Core\Database\Model;

class Blog extends Model
{
    protected static string $table = 'blogs';

    protected static array $casts = [
        'seo' => 'json',
    ];

    public function translations(): array
    {
        $rows = static::db()->connection()->select(
            'SELECT * FROM blog_translations WHERE blog_id = ?',
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
