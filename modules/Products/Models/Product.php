<?php

declare(strict_types=1);

namespace Modules\Products\Models;

use Core\Database\Model;

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

    private ?array $resolved = null;

    private ?array $preloadedTranslations = null;
    private ?array $preloadedVariants = null;
    private ?array $preloadedSpecs = null;
    private ?array $preloadedDimensions = null;
    private bool $hasLoadedDimensions = false;
    private ?array $preloadedMedia = null;

    public static function eagerLoad(array $products, array $relations = ['translations', 'variants', 'specs', 'dimensions', 'media']): array
    {
        if ($products === []) {
            return $products;
        }

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
                "SELECT pm.product_id, pm.id AS attachment_id, pm.type, pm.is_primary, pm.sort_order, m.*
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

            'sku' => $primaryVariant['sku'] ?? (string) ($this->model_code ?? ''),
            'price' => (float) ($primaryVariant['price'] ?? 0),
            'stock_status' => $primaryVariant['stock_status'] ?? 'in_stock',
            'image' => $this->primaryImageUrl(),
            'situ_image' => $this->situImageUrl(),
            'specs' => $specs,

            'gallery' => array_map(
                static fn (array $row): string => (string) $row['path'],
                $this->gallery(),
            ),
            'drawings' => $this->drawings(),
            'situ_images' => $this->situImages(),

            'card_slides' => $this->cardSlides(),

            'variants' => $variants,
            'dimensions' => $dimensions,
        ]);

        return $this->resolved;
    }

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

    public function media(?string $type = null, bool $includeMissing = false): array
    {
        if ($this->preloadedMedia !== null) {
            $rows = $this->preloadedMedia;
        } else {
            $sql = 'SELECT pm.id AS attachment_id, pm.type, pm.is_primary, pm.sort_order, m.*
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

    public function gallery(): array
    {
        return array_values(array_filter(
            $this->media(),
            static fn (array $row): bool => !in_array((string) $row['type'], ['drawing', 'situ'], true),
        ));
    }

    public function mediaBySection(): array
    {
        $rows = $this->media(null, true);

        return [
            'photos' => array_values(array_filter(
                $rows,
                static fn (array $row): bool => !in_array((string) $row['type'], ['drawing', 'situ'], true),
            )),
            'drawings' => array_values(array_filter(
                $rows,
                static fn (array $row): bool => (string) $row['type'] === 'drawing',
            )),
            'situ' => array_values(array_filter(
                $rows,
                static fn (array $row): bool => (string) $row['type'] === 'situ',
            )),
        ];
    }

    public function situImages(): array
    {
        return array_map(
            static fn (array $row): string => (string) $row['path'],
            $this->media('situ'),
        );
    }

    public function drawings(): array
    {
        return array_map(
            static fn (array $row): string => (string) $row['path'],
            $this->media('drawing'),
        );
    }

    public function cardSlides(): array
    {
        $groups = [
            'photo' => $this->gallery(),
            'drawing' => $this->drawings(),
            'situ' => $this->situImages(),
        ];

        $slides = [];
        $seen = [];

        foreach ($groups as $kind => $rows) {
            foreach ($rows as $row) {
                $path = is_array($row) ? (string) ($row['path'] ?? '') : (string) $row;

                if ($path === '' || isset($seen[$path])) {
                    continue;
                }

                $seen[$path] = true;
                $slides[] = ['path' => $path, 'kind' => $kind];
            }
        }

        return $slides;
    }

    private function primaryImageUrl(): string
    {
        $rows = $this->gallery();

        foreach ($rows as $row) {
            if ((int) $row['is_primary'] === 1) {
                return (string) $row['path'];
            }
        }

        $photo = (string) ($rows[0]['path'] ?? '');
        if ($photo !== '') {
            return $photo;
        }

        $drawings = $this->drawings();

        return (string) ($drawings[0] ?? '');
    }

    private function situImageUrl(): string
    {
        $rows = $this->media('situ');

        return (string) ($rows[0]['path'] ?? '');
    }
}
