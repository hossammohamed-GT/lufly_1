<?php

declare(strict_types=1);

namespace Modules\Products\Models;

use Core\Database\Model;

class Product extends Model
{
    protected static string $table = 'products';

    protected static array $fillable = [
        'category_id', 'sku', 'slug', 'price', 'image', 'specs', 'status', 'seo',
    ];

    protected static array $casts = [
        'id' => 'int',
        'price' => 'float',
        'specs' => 'json',
        'seo' => 'json',
    ];

    /** @return array<string, array<string, mixed>> locale => row */
    public function translations(): array
    {
        $rows = static::db()->connection()->select(
            'SELECT * FROM product_translations WHERE product_id = ?',
            [$this->getKey()],
        );

        $out = [];
        foreach ($rows as $row) {
            $out[$row['locale']] = $row;
        }

        return $out;
    }

    /** @return array<string, mixed> merged product + translation for locale (falls back to en) */
    public function translate(string $locale): array
    {
        $translations = $this->translations();
        $fallback = (string) config('localization.fallback', 'en');
        $translation = $translations[$locale] ?? $translations[$fallback] ?? [];

        return array_merge($this->attributes(), [
            'name' => $translation['name'] ?? '',
            'description' => $translation['description'] ?? '',
            'short_description' => $translation['short_description'] ?? '',
        ]);
    }
}
