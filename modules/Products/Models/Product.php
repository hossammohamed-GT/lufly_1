<?php

declare(strict_types=1);

namespace Modules\Products\Models;

use Core\Database\Model;
use Core\Database\Schema\SchemaBuilder;
use Modules\Products\Services\ProductFeatureIconCatalog;
use Modules\Products\Services\ProductVisibility;

/**
 * Base product / main model. Price, stock, images, quick card features,
 * localized specifications, and linked WC seat covers live on related tables.
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

    /** Cached translated payloads keyed by locale + depth flag. */
    private array $resolved = [];

    /** Preloaded relations for batch hydration */
    private ?array $preloadedTranslations = null;
    private ?array $preloadedVariants = null;
    private ?array $preloadedSpecs = null;
    private ?array $preloadedCardFeatures = null;
    private ?array $preloadedDimensions = null;
    private bool $hasLoadedDimensions = false;
    private ?array $preloadedMedia = null;
    private ?array $preloadedCategory = null;
    private bool $hasLoadedCategory = false;
    /** @var Product[]|null */
    private ?array $preloadedSeatCovers = null;

    /** @var array<int, array{preset_key: string, icon_key: string, labels: array<string, string>, defaults: array<string, ?string>}>|null */
    private static ?array $presetCache = null;

    /** @var array<string, bool>|null */
    private static ?array $existingTables = null;

    /**
     * Eager load relations for an array of Product models to eliminate N+1 queries.
     *
     * @param Product[] $products
     * @param string[] $relations
     * @return Product[]
     */
    public static function eagerLoad(
        array $products,
        array $relations = ['translations', 'variants', 'specs', 'card_features', 'dimensions', 'media', 'category', 'seat_covers'],
    ): array {
        if ($products === []) {
            return $products;
        }

        /** @var array<int, Product> $map */
        $map = [];
        $categoryIds = [];
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
                if (in_array('card_features', $relations, true)) {
                    $p->preloadedCardFeatures = [];
                }
                if (in_array('dimensions', $relations, true)) {
                    $p->preloadedDimensions = null;
                    $p->hasLoadedDimensions = true;
                }
                if (in_array('media', $relations, true)) {
                    $p->preloadedMedia = [];
                }
                if (in_array('category', $relations, true)) {
                    $p->preloadedCategory = null;
                    $p->hasLoadedCategory = true;
                    if (!empty($p->category_id)) {
                        $categoryIds[(int) $p->category_id] = true;
                    }
                }
                if (in_array('seat_covers', $relations, true)) {
                    $p->preloadedSeatCovers = [];
                }
            }
        }

        $ids = array_keys($map);
        if ($ids === []) {
            return $products;
        }

        $conn = static::db()->connection();

        if (in_array('translations', $relations, true)) {
            foreach (array_chunk($ids, 400) as $idChunk) {
                $placeholders = implode(',', array_fill(0, count($idChunk), '?'));
                $tRows = $conn->select(
                    "SELECT * FROM product_translations WHERE product_id IN ({$placeholders})",
                    $idChunk,
                );
                foreach ($tRows as $row) {
                    $pId = (int) $row['product_id'];
                    if (isset($map[$pId])) {
                        $map[$pId]->preloadedTranslations[(string) $row['locale']] = $row;
                    }
                }
            }
        }

        if (in_array('variants', $relations, true)) {
            foreach (array_chunk($ids, 400) as $idChunk) {
                $placeholders = implode(',', array_fill(0, count($idChunk), '?'));
                $vRows = $conn->select(
                    "SELECT * FROM product_variants WHERE product_id IN ({$placeholders}) AND deleted_at IS NULL ORDER BY sort_order ASC, id ASC",
                    $idChunk,
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
        }

        if (in_array('specs', $relations, true)) {
            $sRows = [];
            foreach (array_chunk($ids, 400) as $idChunk) {
                $placeholders = implode(',', array_fill(0, count($idChunk), '?'));
                foreach ($conn->select(
                    "SELECT id, product_id, spec_key, spec_value, unit FROM product_specifications WHERE product_id IN ({$placeholders}) AND variant_id IS NULL ORDER BY sort_order ASC, id ASC",
                    $idChunk,
                ) as $sr) {
                    $sRows[] = $sr;
                }
            }
            $specIds = array_values(array_filter(array_map(static fn (array $r): int => (int) ($r['id'] ?? 0), $sRows)));
            $specTranslations = [];
            if ($specIds !== [] && self::tableExists('product_specification_translations')) {
                foreach (array_chunk($specIds, 400) as $specChunk) {
                    $sPlaceholders = implode(',', array_fill(0, count($specChunk), '?'));
                    $stRows = $conn->select(
                        "SELECT specification_id, locale, spec_key, spec_value
                         FROM product_specification_translations
                         WHERE specification_id IN ({$sPlaceholders})",
                        $specChunk,
                    );
                    foreach ($stRows as $st) {
                        $sid = (int) $st['specification_id'];
                        $specTranslations[$sid][(string) $st['locale']] = [
                            'spec_key' => (string) $st['spec_key'],
                            'spec_value' => (string) $st['spec_value'],
                        ];
                    }
                }
            }
            foreach ($sRows as $row) {
                $pId = (int) $row['product_id'];
                $sId = (int) $row['id'];
                if (isset($map[$pId])) {
                    $row['translations'] = $specTranslations[$sId] ?? [];
                    $map[$pId]->preloadedSpecs[] = $row;
                }
            }
        }

        if (in_array('card_features', $relations, true) && self::tableExists('product_card_features')) {
            $fRows = [];
            foreach (array_chunk($ids, 400) as $idChunk) {
                $placeholders = implode(',', array_fill(0, count($idChunk), '?'));
                foreach ($conn->select(
                    "SELECT id, product_id, preset_id, icon_key, sort_order
                     FROM product_card_features
                     WHERE product_id IN ({$placeholders})
                     ORDER BY sort_order ASC, id ASC",
                    $idChunk,
                ) as $fr) {
                    $fRows[] = $fr;
                }
            }
            $fIds = array_values(array_filter(array_map(static fn (array $r): int => (int) ($r['id'] ?? 0), $fRows)));
            $fTranslations = [];
            if ($fIds !== [] && self::tableExists('product_card_feature_translations')) {
                foreach (array_chunk($fIds, 400) as $fChunk) {
                    $fPlaceholders = implode(',', array_fill(0, count($fChunk), '?'));
                    $ftRows = $conn->select(
                        "SELECT feature_id, locale, label, feature_value
                         FROM product_card_feature_translations
                         WHERE feature_id IN ({$fPlaceholders})",
                        $fChunk,
                    );
                    foreach ($ftRows as $ft) {
                        $fid = (int) $ft['feature_id'];
                        $fTranslations[$fid][(string) $ft['locale']] = [
                            'label' => $ft['label'] !== null ? (string) $ft['label'] : null,
                            'value' => (string) $ft['feature_value'],
                        ];
                    }
                }
            }
            foreach ($fRows as $row) {
                $pId = (int) $row['product_id'];
                $fId = (int) $row['id'];
                if (isset($map[$pId])) {
                    $row['translations'] = $fTranslations[$fId] ?? [];
                    $map[$pId]->preloadedCardFeatures[] = $row;
                }
            }
        }

        if (in_array('dimensions', $relations, true)) {
            foreach (array_chunk($ids, 400) as $idChunk) {
                $placeholders = implode(',', array_fill(0, count($idChunk), '?'));
                $dRows = $conn->select(
                    "SELECT product_id, width_mm, height_mm, depth_mm, weight_kg FROM product_dimensions WHERE product_id IN ({$placeholders}) AND variant_id IS NULL",
                    $idChunk,
                );
                foreach ($dRows as $row) {
                    $pId = (int) $row['product_id'];
                    if (isset($map[$pId])) {
                        $map[$pId]->preloadedDimensions = $row;
                        $map[$pId]->hasLoadedDimensions = true;
                    }
                }
            }
        }

        if (in_array('media', $relations, true)) {
            foreach (array_chunk($ids, 400) as $idChunk) {
                $placeholders = implode(',', array_fill(0, count($idChunk), '?'));
                $mRows = $conn->select(
                    "SELECT pm.product_id, pm.id AS attachment_id, pm.type, pm.is_primary, pm.sort_order, m.*
                     FROM product_media pm
                     INNER JOIN media m ON m.id = pm.media_id
                     WHERE pm.product_id IN ({$placeholders}) AND (pm.variant_id IS NULL)
                     ORDER BY pm.is_primary DESC, pm.sort_order ASC, pm.id ASC",
                    $idChunk,
                );
                foreach ($mRows as $row) {
                    $pId = (int) $row['product_id'];
                    if (isset($map[$pId])) {
                        $map[$pId]->preloadedMedia[] = $row;
                    }
                }
            }
        }

        if (in_array('category', $relations, true) && $categoryIds !== []) {
            $cIds = array_keys($categoryIds);
            $cPlaceholders = implode(',', array_fill(0, count($cIds), '?'));
            $cRows = $conn->select(
                "SELECT id, slug FROM categories WHERE id IN ({$cPlaceholders}) AND deleted_at IS NULL",
                $cIds,
            );
            $catById = [];
            foreach ($cRows as $row) {
                $catById[(int) $row['id']] = [
                    'slug' => (string) $row['slug'],
                    'names' => [],
                ];
            }
            if ($catById !== []) {
                $ctRows = $conn->select(
                    "SELECT category_id, locale, name FROM category_translations WHERE category_id IN ({$cPlaceholders})",
                    $cIds,
                );
                foreach ($ctRows as $row) {
                    $cId = (int) $row['category_id'];
                    if (isset($catById[$cId])) {
                        $catById[$cId]['names'][(string) $row['locale']] = (string) $row['name'];
                    }
                }
                foreach ($map as $p) {
                    $cId = (int) ($p->category_id ?? 0);
                    if ($cId > 0 && isset($catById[$cId])) {
                        $p->preloadedCategory = $catById[$cId];
                    }
                }
            }
        }

        if (in_array('seat_covers', $relations, true)) {
            $scRows = [];
            foreach (array_chunk($ids, 400) as $idChunk) {
                $placeholders = implode(',', array_fill(0, count($idChunk), '?'));
                foreach ($conn->select(
                    "SELECT pr.product_id, pr.related_product_id
                     FROM product_relations pr
                     INNER JOIN products sp ON sp.id = pr.related_product_id AND sp.deleted_at IS NULL
                     WHERE pr.product_id IN ({$placeholders})
                       AND pr.relation_type = 'seat_cover'
                     ORDER BY pr.id ASC",
                    $idChunk,
                ) as $scr) {
                    $scRows[] = $scr;
                }
            }
            if ($scRows !== []) {
                $seatIds = array_values(array_unique(array_map(
                    static fn (array $r): int => (int) $r['related_product_id'],
                    $scRows,
                )));
                $seatModels = [];
                if ($seatIds !== []) {
                    $loadedSeats = static::query()->whereIn('id', $seatIds)->get();
                    static::eagerLoad($loadedSeats, ['translations', 'variants', 'specs', 'card_features', 'dimensions', 'media', 'category']);
                    foreach ($loadedSeats as $sm) {
                        if ($sm instanceof self) {
                            $seatModels[(int) $sm->getKey()] = $sm;
                        }
                    }
                }
                foreach ($scRows as $sc) {
                    $wcId = (int) $sc['product_id'];
                    $seatId = (int) $sc['related_product_id'];
                    if (isset($map[$wcId], $seatModels[$seatId])) {
                        $map[$wcId]->preloadedSeatCovers[] = $seatModels[$seatId];
                    }
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
            $out[(string) $row['locale']] = $row;
        }

        return $this->preloadedTranslations = $out;
    }

    /**
     * Merged product payload for a locale, enriched with the primary variant
     * (sku / price / stock), primary image, localized specifications, 4-6 card
     * features, and linked WC seat covers.
     *
     * @return array<string, mixed>
     */
    public function translate(string $locale, bool $includeSeatCovers = true): array
    {
        $cacheKey = $locale . ':' . ($includeSeatCovers ? '1' : '0');
        if (isset($this->resolved[$cacheKey])) {
            return $this->resolved[$cacheKey];
        }

        $fallback = (string) config('localization.fallback', 'en');
        $translations = $this->translations();
        $translation = $translations[$locale] ?? $translations[$fallback] ?? [];
        $sourceDescription = (string) ($translations[$fallback]['description'] ?? $translation['description'] ?? '');

        $variants = $this->variants();
        $primaryVariant = $variants[0] ?? null;
        $specs = $this->specsForLocale($locale, $fallback);
        $cardFeatures = $this->cardFeatures($locale, $fallback);
        $dimensions = $this->dimensions();
        $category = $this->categoryInfo();
        $categorySlug = (string) ($category['slug'] ?? '');
        $categoryNames = is_array($category['names'] ?? null) ? $category['names'] : [];
        $categoryName = (string) ($categoryNames[$locale] ?? $categoryNames[$fallback] ?? '');

        $seatCovers = $includeSeatCovers ? $this->seatCovers($locale) : [];

        return $this->resolved[$cacheKey] = array_merge($this->attributes(), [
            'locale' => $locale,
            'name' => $translation['name'] ?? '',
            'description' => $translation['description'] ?? '',
            'short_description' => $translation['short_description'] ?? '',
            'source_description' => $sourceDescription,
            'category_slug' => $categorySlug,
            'category_name' => $categoryName,

            /* compatibility aliases for storefront views */
            'sku' => $primaryVariant['sku'] ?? (string) ($this->model_code ?? ''),
            'price' => (float) ($primaryVariant['price'] ?? 0),
            'stock_status' => $primaryVariant['stock_status'] ?? 'in_stock',
            'image' => $this->primaryImageUrl(),
            'situ_image' => $this->situImageUrl(),
            'specs' => $specs,
            'card_features' => $cardFeatures,
            'seat_covers' => $seatCovers,

            /* three independent image groups, each holding any number of
               images: product photos (type main/gallery), technical drawings
               (type drawing), installed shots (type situ) */
            'gallery' => array_values(array_unique(array_map(
                static fn (array $row): string => (string) $row['path'],
                $this->gallery(),
            ))),
            'drawings' => $this->drawings(),
            'situ_images' => $this->situImages(),

            /* every image of the product as the cards rotate them on hover,
               each one labelled with the section it came from */
            'card_slides' => $this->cardSlides(),

            /* full related data */
            'variants' => $variants,
            'dimensions' => $dimensions,
        ]);
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
     * Raw specification rows (with preloaded translation maps when available).
     *
     * @return array<int, array<string, mixed>>
     */
    public function specs(): array
    {
        if ($this->preloadedSpecs !== null) {
            return $this->preloadedSpecs;
        }

        $conn = static::db()->connection();
        $rows = $conn->select(
            'SELECT id, spec_key, spec_value, unit FROM product_specifications
             WHERE product_id = ? AND variant_id IS NULL
             ORDER BY sort_order ASC, id ASC',
            [$this->getKey()],
        );

        $specIds = array_values(array_filter(array_map(static fn (array $r): int => (int) ($r['id'] ?? 0), $rows)));
        $translations = [];
        if ($specIds !== [] && self::tableExists('product_specification_translations')) {
            $placeholders = implode(',', array_fill(0, count($specIds), '?'));
            $tRows = $conn->select(
                "SELECT specification_id, locale, spec_key, spec_value
                 FROM product_specification_translations
                 WHERE specification_id IN ({$placeholders})",
                $specIds,
            );
            foreach ($tRows as $tr) {
                $translations[(int) $tr['specification_id']][(string) $tr['locale']] = [
                    'spec_key' => (string) $tr['spec_key'],
                    'spec_value' => (string) $tr['spec_value'],
                ];
            }
        }

        foreach ($rows as &$row) {
            $row['translations'] = $translations[(int) ($row['id'] ?? 0)] ?? [];
        }
        unset($row);

        return $this->preloadedSpecs = $rows;
    }

    /**
     * Specifications localized for $locale, while preserving raw_key / raw_value
     * for canonical finish and dimension extractors.
     *
     * @return array<int, array{spec_key: string, spec_value: string, unit: ?string, raw_key: string, raw_value: string}>
     */
    public function specsForLocale(string $locale, string $fallback = 'en'): array
    {
        $out = [];
        foreach ($this->specs() as $row) {
            $rawKey = (string) ($row['spec_key'] ?? '');
            $rawVal = (string) ($row['spec_value'] ?? '');
            $trMap = is_array($row['translations'] ?? null) ? $row['translations'] : [];
            $chosen = $trMap[$locale] ?? $trMap[$fallback] ?? null;

            $out[] = [
                'spec_key' => (string) ($chosen['spec_key'] ?? $rawKey),
                'spec_value' => (string) ($chosen['spec_value'] ?? $rawVal),
                'unit' => isset($row['unit']) && $row['unit'] !== null ? (string) $row['unit'] : null,
                'raw_key' => $rawKey,
                'raw_value' => $rawVal,
            ];
        }

        return $out;
    }

    /**
     * Ordered 4-6 card features/specs from product_card_features + presets,
     * localized for $locale.
     *
     * @return array<int, array{icon: string, label: string, value: string, preset_key: ?string}>
     */
    public function cardFeatures(string $locale, string $fallback = 'en'): array
    {
        if (!self::tableExists('product_card_features')) {
            return [];
        }

        if ($this->preloadedCardFeatures === null) {
            $conn = static::db()->connection();
            $rows = $conn->select(
                'SELECT id, preset_id, icon_key, sort_order
                 FROM product_card_features
                 WHERE product_id = ?
                 ORDER BY sort_order ASC, id ASC',
                [$this->getKey()],
            );
            $fIds = array_values(array_filter(array_map(static fn (array $r): int => (int) ($r['id'] ?? 0), $rows)));
            $trByFeature = [];
            if ($fIds !== [] && self::tableExists('product_card_feature_translations')) {
                $placeholders = implode(',', array_fill(0, count($fIds), '?'));
                $tRows = $conn->select(
                    "SELECT feature_id, locale, label, feature_value
                     FROM product_card_feature_translations
                     WHERE feature_id IN ({$placeholders})",
                    $fIds,
                );
                foreach ($tRows as $tr) {
                    $trByFeature[(int) $tr['feature_id']][(string) $tr['locale']] = [
                        'label' => $tr['label'] !== null ? (string) $tr['label'] : null,
                        'value' => (string) $tr['feature_value'],
                    ];
                }
            }
            foreach ($rows as &$row) {
                $row['translations'] = $trByFeature[(int) ($row['id'] ?? 0)] ?? [];
            }
            unset($row);
            $this->preloadedCardFeatures = $rows;
        }

        if ($this->preloadedCardFeatures === []) {
            return [];
        }

        $presets = self::presets();
        $out = [];
        foreach ($this->preloadedCardFeatures as $row) {
            $presetId = isset($row['preset_id']) && $row['preset_id'] !== null ? (int) $row['preset_id'] : 0;
            $preset = $presetId > 0 ? ($presets[$presetId] ?? null) : null;
            $iconRaw = trim((string) ($row['icon_key'] ?? ''));
            if ($iconRaw === '' && $preset !== null) {
                $iconRaw = $preset['icon_key'];
            }

            $trMap = is_array($row['translations'] ?? null) ? $row['translations'] : [];
            $chosen = $trMap[$locale] ?? $trMap[$fallback] ?? reset($trMap) ?: [];

            $customLabel = trim((string) ($chosen['label'] ?? ''));
            $presetLabel = $preset !== null
                ? (string) ($preset['labels'][$locale] ?? $preset['labels'][$fallback] ?? '')
                : '';
            $label = $customLabel !== '' ? $customLabel : $presetLabel;

            $value = trim((string) ($chosen['value'] ?? ''));
            if ($value === '' && $preset !== null) {
                $value = trim((string) ($preset['defaults'][$locale] ?? $preset['defaults'][$fallback] ?? ''));
            }

            if ($label === '' || $value === '') {
                continue;
            }

            $out[] = [
                'icon' => ProductFeatureIconCatalog::normalize($iconRaw),
                'label' => $label,
                'value' => $value,
                'preset_key' => $preset['preset_key'] ?? null,
            ];
        }

        return $out;
    }

    /**
     * Compatible Toilet Seat Covers linked to this WC via product_relations
     * (`relation_type = 'seat_cover'`), translated for $locale.
     *
     * @return array<int, array<string, mixed>>
     */
    public function seatCovers(string $locale): array
    {
        if ($this->preloadedSeatCovers === null) {
            $conn = static::db()->connection();
            $rows = $conn->select(
                "SELECT pr.related_product_id
                 FROM product_relations pr
                 INNER JOIN products sp ON sp.id = pr.related_product_id AND sp.deleted_at IS NULL
                 WHERE pr.product_id = ?
                   AND pr.relation_type = 'seat_cover'
                 ORDER BY pr.id ASC",
                [$this->getKey()],
            );
            $seatIds = array_values(array_unique(array_map(
                static fn (array $r): int => (int) $r['related_product_id'],
                $rows,
            )));
            $models = [];
            if ($seatIds !== []) {
                $loaded = static::query()->whereIn('id', $seatIds)->get();
                static::eagerLoad($loaded, ['translations', 'variants', 'specs', 'card_features', 'dimensions', 'media', 'category']);
                $byId = [];
                foreach ($loaded as $m) {
                    if ($m instanceof self) {
                        $byId[(int) $m->getKey()] = $m;
                    }
                }
                foreach ($seatIds as $sid) {
                    if (isset($byId[$sid])) {
                        $models[] = $byId[$sid];
                    }
                }
            }
            $this->preloadedSeatCovers = $models;
        }

        $out = [];
        foreach ($this->preloadedSeatCovers as $seatModel) {
            if (!ProductVisibility::isSellable($seatModel)) {
                continue;
            }
            $out[] = $seatModel->translate($locale, false);
        }

        return $out;
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

    /** @return array{slug: string, names: array<string, string>}|null */
    private function categoryInfo(): ?array
    {
        if ($this->hasLoadedCategory) {
            return $this->preloadedCategory;
        }

        $this->hasLoadedCategory = true;
        $categoryId = (int) ($this->category_id ?? 0);
        if ($categoryId <= 0) {
            return $this->preloadedCategory = null;
        }

        $conn = static::db()->connection();
        $row = $conn->selectOne(
            'SELECT id, slug FROM categories WHERE id = ? AND deleted_at IS NULL LIMIT 1',
            [$categoryId],
        );
        if ($row === null) {
            return $this->preloadedCategory = null;
        }

        $names = [];
        foreach ($conn->select('SELECT locale, name FROM category_translations WHERE category_id = ?', [$categoryId]) as $tr) {
            $names[(string) $tr['locale']] = (string) $tr['name'];
        }

        return $this->preloadedCategory = [
            'slug' => (string) $row['slug'],
            'names' => $names,
        ];
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
            if (!$includeMissing && in_array((string) ($row['status'] ?? ''), ['missing', 'hidden'], true)) {
                continue;
            }
            if ($type !== null && ($row['type'] ?? '') !== $type) {
                continue;
            }
            $filtered[] = $row;
        }

        return $filtered;
    }

    /** @return array<int, array<string, mixed>> */
    public function gallery(): array
    {
        return array_values(array_filter(
            $this->media(),
            static fn (array $row): bool => !in_array((string) $row['type'], ['drawing', 'situ'], true),
        ));
    }

    /**
     * @return array{photos: array<int, array<string, mixed>>, drawings: array<int, array<string, mixed>>, situ: array<int, array<string, mixed>>}
     */
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

    /** @return array<int, string> */
    public function situImages(): array
    {
        return array_values(array_unique(array_map(
            static fn (array $row): string => (string) $row['path'],
            $this->media('situ'),
        )));
    }

    /** @return array<int, string> */
    public function drawings(): array
    {
        return array_values(array_unique(array_map(
            static fn (array $row): string => (string) $row['path'],
            $this->media('drawing'),
        )));
    }

    /** @return array<int, array{path: string, kind: string}> */
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

    /**
     * @return array<int, array{preset_key: string, icon_key: string, labels: array<string, string>, defaults: array<string, ?string>}>
     */
    private static function presets(): array
    {
        if (self::$presetCache !== null) {
            return self::$presetCache;
        }

        if (!self::tableExists('product_feature_presets')) {
            return self::$presetCache = [];
        }

        $conn = static::db()->connection();
        $rows = $conn->select('SELECT id, preset_key, icon_key FROM product_feature_presets WHERE is_active = 1');
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['id']] = [
                'preset_key' => (string) $row['preset_key'],
                'icon_key' => (string) $row['icon_key'],
                'labels' => [],
                'defaults' => [],
            ];
        }

        if ($map !== [] && self::tableExists('product_feature_preset_translations')) {
            $tRows = $conn->select('SELECT preset_id, locale, label, placeholder_value FROM product_feature_preset_translations');
            foreach ($tRows as $tr) {
                $pid = (int) $tr['preset_id'];
                if (isset($map[$pid])) {
                    $loc = (string) $tr['locale'];
                    $map[$pid]['labels'][$loc] = (string) $tr['label'];
                    $map[$pid]['defaults'][$loc] = $tr['placeholder_value'] !== null ? (string) $tr['placeholder_value'] : null;
                }
            }
        }

        return self::$presetCache = $map;
    }

    private static function tableExists(string $table): bool
    {
        if (self::$existingTables === null) {
            try {
                $names = (new SchemaBuilder(static::db()->connection()))->listTables();
                self::$existingTables = [];
                foreach ($names as $name) {
                    self::$existingTables[(string) $name] = true;
                }
            } catch (\Throwable) {
                self::$existingTables = [];
            }
        }

        return isset(self::$existingTables[$table]);
    }

    public static function clearStaticCaches(): void
    {
        self::$presetCache = null;
        self::$existingTables = null;
    }
}
