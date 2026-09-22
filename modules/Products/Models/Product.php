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

    /** Preloaded relations for batch hydration */
    private ?array $preloadedTranslations = null;
    private ?array $preloadedVariants = null;
    private ?array $preloadedSpecs = null;
    private ?array $preloadedDimensions = null;
    private bool $hasLoadedDimensions = false;
    private ?array $preloadedMedia = null;

    /**
     * Eager load relations for an array of Product models to eliminate N+1 queries.
     *
     * @param Product[] $products
     * @param string[] $relations
     * @return Product[]
     */
    public static function eagerLoad(array $products, array $relations = ['translations', 'variants', 'specs', 'dimensions', 'media']): array
    {
        if ($products === []) {
            return $products;
        }

        /** @var array<int, Product> $map */
        $map = [];
        foreach ($products as $p) {
            if ($p instanceof self && $p->getKey() !== null) {
                $id = (int) $p->getKey();
                $map[$id] = $p;
                if (in_array('translations', $relations, true)) {
                    $p->preloadedTranslations = [];
                }
                if (in_array('variants', $relations, true)) {
                    $p->preloadedVariants = [];
                }
                if (in_array('specs', $relations, true)) {
                    $p->preloadedSpecs = [];
                }
                if (in_array('dimensions', $relations, true)) {
                    $p->preloadedDimensions = null;
                    $p->hasLoadedDimensions = true;
                }
                if (in_array('media', $relations, true)) {
                    $p->preloadedMedia = [];
                }
            }
        }

        $ids = array_keys($map);
        if ($ids === []) {
            return $products;
        }

        $conn = static::db()->connection();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        if (in_array('translations', $relations, true)) {
            $tRows = $conn->select(
                "SELECT * FROM product_translations WHERE product_id IN ({$placeholders})",
                $ids,
            );
            foreach ($tRows as $row) {
                $pId = (int) $row['product_id'];
                if (isset($map[$pId])) {
                    $map[$pId]->preloadedTranslations[$row['locale']] = $row;
                }
            }
        }

        if (in_array('variants', $relations, true)) {
            $vRows = $conn->select(
                "SELECT * FROM product_variants WHERE product_id IN ({$placeholders}) AND deleted_at IS NULL ORDER BY sort_order ASC, id ASC",
                $ids,
            );
            foreach ($vRows as $row) {
                $pId = (int) $row['product_id'];
                if (isset($map[$pId])) {
                    $map[$pId]->preloadedVariants[] = array_merge($row, [
                        'id' => (int) $row['id'],
                        'price' => (float) $row['price'],
                    ]);
                }
            }
        }

        if (in_array('specs', $relations, true)) {
            $sRows = $conn->select(
                "SELECT product_id, spec_key, spec_value, unit FROM product_specifications WHERE product_id IN ({$placeholders}) AND variant_id IS NULL ORDER BY sort_order ASC, id ASC",
                $ids,
            );
            foreach ($sRows as $row) {
                $pId = (int) $row['product_id'];
                if (isset($map[$pId])) {
                    $map[$pId]->preloadedSpecs[] = $row;
                }
            }
        }

        if (in_array('dimensions', $relations, true)) {
            $dRows = $conn->select(
                "SELECT product_id, width_mm, height_mm, depth_mm, weight_kg FROM product_dimensions WHERE product_id IN ({$placeholders}) AND variant_id IS NULL",
                $ids,
            );
            foreach ($dRows as $row) {
                $pId = (int) $row['product_id'];
                if (isset($map[$pId])) {
                    $map[$pId]->preloadedDimensions = $row;
                    $map[$pId]->hasLoadedDimensions = true;
                }
            }
        }

        if (in_array('media', $relations, true)) {
            $mRows = $conn->select(
                "SELECT pm.product_id, pm.type, pm.is_primary, pm.sort_order, m.*
                 FROM product_media pm
                 INNER JOIN media m ON m.id = pm.media_id
                 WHERE pm.product_id IN ({$placeholders}) AND (pm.variant_id IS NULL)
                 ORDER BY pm.is_primary DESC, pm.sort_order ASC, pm.id ASC",
                $ids,
            );
            foreach ($mRows as $row) {
                $pId = (int) $row['product_id'];
                if (isset($map[$pId])) {
                    $map[$pId]->preloadedMedia[] = $row;
                }
            }
        }

        return $products;
    }

    /** @return array<string, array<string, mixed>> locale => row */
    public function translations(): array
    {
        if ($this->preloadedTranslations !== null) {
            return $this->preloadedTranslations;
        }

        $rows = static::db()->connection()->select(
            'SELECT * FROM product_translations WHERE product_id = ?',
            [$this->getKey()],
        );

        $out = [];
        foreach ($rows as $row) {
            $out[$row['locale']] = $row;
        }

        return $this->preloadedTranslations = $out;
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

            /* three independent image groups, each holding any number of
               images: product photos, technical drawings, installed shots */
            'gallery' => array_map(
                static fn (array $row): string => (string) $row['path'],
                $this->gallery(),
            ),
            'drawings' => $this->drawings(),
            'situ_images' => $this->situImages(),

            /* full related data */
            'variants' => $variants,
            'dimensions' => $dimensions,
        ]);

        return $this->resolved;
    }

    /** @return array<int, array<string, mixed>> ordered variant rows */
    public function variants(): array
    {
        if ($this->preloadedVariants !== null) {
            return $this->preloadedVariants;
        }

        $rows = static::db()->connection()->select(
            'SELECT * FROM product_variants WHERE product_id = ? AND deleted_at IS NULL ORDER BY sort_order ASC, id ASC',
            [$this->getKey()],
        );

        return $this->preloadedVariants = array_map(static fn (array $row): array => array_merge($row, [
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
        if ($this->preloadedSpecs !== null) {
            return $this->preloadedSpecs;
        }

        $rows = static::db()->connection()->select(
            'SELECT spec_key, spec_value, unit FROM product_specifications
             WHERE product_id = ? AND variant_id IS NULL
             ORDER BY sort_order ASC, id ASC',
            [$this->getKey()],
        );

        return $this->preloadedSpecs = $rows;
    }

    /** @return array<string, mixed>|null */
    public function dimensions(): ?array
    {
        if ($this->hasLoadedDimensions) {
            return $this->preloadedDimensions;
        }

        $row = static::db()->connection()->selectOne(
            'SELECT width_mm, height_mm, depth_mm, weight_kg FROM product_dimensions
             WHERE product_id = ? AND variant_id IS NULL LIMIT 1',
            [$this->getKey()],
        );

        $this->hasLoadedDimensions = true;
        return $this->preloadedDimensions = $row;
    }

    /**
     * Gallery + drawings from the media library, ordered.
     *
     * Media rows imported from the legacy system whose file was never
     * exported carry status "missing"; they are skipped by default so the
     * storefront never renders a broken image. Pass $includeMissing to audit
     * what is still outstanding.
     */
    public function media(?string $type = null, bool $includeMissing = false): array
    {
        if ($this->preloadedMedia !== null) {
            $rows = $this->preloadedMedia;
        } else {
            $sql = 'SELECT pm.type, pm.is_primary, pm.sort_order, m.*
                    FROM product_media pm
                    INNER JOIN media m ON m.id = pm.media_id
                    WHERE pm.product_id = ? AND (pm.variant_id IS NULL)
                    ORDER BY pm.is_primary DESC, pm.sort_order ASC, pm.id ASC';
            $rows = static::db()->connection()->select($sql, [$this->getKey()]);
            $this->preloadedMedia = $rows;
        }

        $filtered = [];
        foreach ($rows as $row) {
            if (!$includeMissing && ($row['status'] ?? '') === 'missing') {
                continue;
            }
            if ($type !== null && ($row['type'] ?? '') !== $type) {
                continue;
            }
            $filtered[] = $row;
        }

        return $filtered;
    }

    /**
     * Product photos for the main gallery: everything that is neither a
     * technical drawing nor an installed ("situ") shot. Primary first.
     *
     * @return array<int, array<string, mixed>>
     */
    public function gallery(): array
    {
        return array_values(array_filter(
            $this->media(),
            static fn (array $row): bool => !in_array((string) $row['type'], ['drawing', 'situ'], true),
        ));
    }

    /**
     * Installed / in-situ photos (the product mounted on location).
     *
     * @return array<int, string>
     */
    public function situImages(): array
    {
        return array_map(
            static fn (array $row): string => (string) $row['path'],
            $this->media('situ'),
        );
    }

    /**
     * Technical drawing URLs for this product, empty when none was exported.
     *
     * @return array<int, string>
     */
    public function drawings(): array
    {
        return array_map(
            static fn (array $row): string => (string) $row['path'],
            $this->media('drawing'),
        );
    }

    /** Primary image URL (the flagged primary first, then any photo). */
    private function primaryImageUrl(): string
    {
        $rows = $this->gallery();

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
