<?php

declare(strict_types=1);

namespace Modules\Products\Models;

use Core\Database\Model;

/**
 * Base product / main model. Price, stock, images and specs live on the
 * related tables (product_variants, product_media, product_specifications).
 */
class Product extends Model
{
    protected static string $table = 'products';

    protected static array $fillable = [
        'model_code', 'slug', 'category_id', 'collection_id', 'brand_id',
        'status', 'is_featured', 'sort_order',
    ];

    protected static array $casts = [
        'id' => 'int',
        'category_id' => 'int',
        'collection_id' => 'int',
        'brand_id' => 'int',
        'is_featured' => 'bool',
        'sort_order' => 'int',
    ];

    /** Cached translated payload (used by translate()). */
    private ?array $resolved = null;

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

    /**
     * Merged product payload for a locale, enriched with the primary variant
     * (sku / price / stock), primary image, and flexible specifications so
     * storefront views keep a simple array shape.
     *
     * @return array<string, mixed>
     */
    public function translate(string $locale): array
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        $translations = $this->translations();
        $fallback = (string) config('localization.fallback', 'en');
        $translation = $translations[$locale] ?? $translations[$fallback] ?? [];

        $variants = $this->variants();
        $primaryVariant = $variants[0] ?? null;
        $specs = $this->specs();
        $dimensions = $this->dimensions();

        $this->resolved = array_merge($this->attributes(), [
            'name' => $translation['name'] ?? '',
            'description' => $translation['description'] ?? '',
            'short_description' => $translation['short_description'] ?? '',

            /* compatibility aliases for storefront views */
            'sku' => $primaryVariant['sku'] ?? (string) ($this->model_code ?? ''),
            'price' => (float) ($primaryVariant['price'] ?? 0),
            'stock_status' => $primaryVariant['stock_status'] ?? 'in_stock',
            'image' => $this->primaryImageUrl(),
            'situ_image' => $this->situImageUrl(),
            'specs' => $specs,

            /* full related data */
            'variants' => $variants,
            'dimensions' => $dimensions,
        ]);

        return $this->resolved;
    }

    /** @return array<int, array<string, mixed>> ordered variant rows */
    public function variants(): array
    {
        $rows = static::db()->connection()->select(
            'SELECT * FROM product_variants WHERE product_id = ? AND deleted_at IS NULL ORDER BY sort_order ASC, id ASC',
            [$this->getKey()],
        );

        return array_map(static fn (array $row): array => array_merge($row, [
            'id' => (int) $row['id'],
            'price' => (float) $row['price'],
        ]), $rows);
    }

    /**
     * Flexible specifications as ordered key/value/unit rows.
     *
     * @return array<int, array{spec_key: string, spec_value: string, unit: ?string}>
     */
    public function specs(): array
    {
        $rows = static::db()->connection()->select(
            'SELECT spec_key, spec_value, unit FROM product_specifications
             WHERE product_id = ? AND variant_id IS NULL
             ORDER BY sort_order ASC, id ASC',
            [$this->getKey()],
        );

        return $rows;
    }

    /** @return array<string, mixed>|null */
    public function dimensions(): ?array
    {
        $row = static::db()->connection()->selectOne(
            'SELECT width_mm, height_mm, depth_mm, weight_kg FROM product_dimensions
             WHERE product_id = ? AND variant_id IS NULL LIMIT 1',
            [$this->getKey()],
        );

        return $row;
    }

    /** Gallery + drawings from the media library, ordered. */
    public function media(?string $type = null): array
    {
        $sql = 'SELECT pm.type, pm.is_primary, pm.sort_order, m.*
                FROM product_media pm
                INNER JOIN media m ON m.id = pm.media_id
                WHERE pm.product_id = ? AND (pm.variant_id IS NULL)';
        $bindings = [$this->getKey()];

        if ($type !== null) {
            $sql .= ' AND pm.type = ?';
            $bindings[] = $type;
        }

        $sql .= ' ORDER BY pm.is_primary DESC, pm.sort_order ASC, pm.id ASC';

        return static::db()->connection()->select($sql, $bindings);
    }

    /** Primary image URL (media library first, then any gallery item). */
    private function primaryImageUrl(): string
    {
        $rows = $this->media();

        foreach ($rows as $row) {
            if ((int) $row['is_primary'] === 1) {
                return (string) $row['path'];
            }
        }

        return (string) ($rows[0]['path'] ?? '');
    }

    /** First "situ" (installed on location) image URL, empty string when none. */
    private function situImageUrl(): string
    {
        $rows = $this->media('situ');

        return (string) ($rows[0]['path'] ?? '');
    }
}
