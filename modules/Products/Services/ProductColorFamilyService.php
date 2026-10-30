<?php

declare(strict_types=1);

namespace Modules\Products\Services;

use Modules\Products\Models\Product;

/**
 * Resolves reviewed cross-product colour/finish families.
 *
 * A family never changes the identity of its members. It only collapses
 * duplicate catalog cards and supplies each member's genuine translated
 * payload to the same-card colour selector. Relationships are seeded from
 * database/seeders/data/product-color-families.json; they are never inferred
 * from a model-code prefix at runtime.
 */
final class ProductColorFamilyService
{
    /** @var array<int, array<int, int>>|null product id => ordered family ids */
    private ?array $memberships = null;

    /** @var array<int, bool>|null product id => has non-empty source name */
    private ?array $namedIds = null;

    /** @var array<int, Product> */
    private array $models = [];

    /**
     * Keep the first row in the requested sort order and remove later members
     * of the same family. This happens before pagination, so two colours can
     * never appear as separate cards on different pages.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    public function collapseRows(array $rows): array
    {
        $memberships = $this->memberships();
        if ($memberships === []) {
            return array_values($rows);
        }

        $namedIds = $this->namedIds();
        $seen = [];
        $collapsed = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $family = $memberships[$id] ?? null;
            $key = $family !== null ? 'family-' . $family[0] : 'product-' . $id;
            if (isset($seen[$key])) {
                $slot = $seen[$key];
                $current = $collapsed[$slot];
                $currentId = (int) ($current['id'] ?? 0);
                $currentFeatured = (int) ($current['is_featured'] ?? 0) === 1;
                $candidateFeatured = (int) ($row['is_featured'] ?? 0) === 1;
                $currentOrder = (int) ($current['sort_order'] ?? PHP_INT_MAX);
                $candidateOrder = (int) ($row['sort_order'] ?? PHP_INT_MAX);

                if ($candidateFeatured && !$currentFeatured) {
                    $collapsed[$slot] = $row;
                } elseif ($candidateFeatured && $currentFeatured) {
                    if ($candidateOrder < $currentOrder || ($candidateOrder === $currentOrder && !isset($namedIds[$currentId]) && isset($namedIds[$id]))) {
                        $collapsed[$slot] = $row;
                    }
                } elseif (!$currentFeatured && !isset($namedIds[$currentId]) && isset($namedIds[$id])) {
                    $collapsed[$slot] = $row;
                }
                continue;
            }
            $seen[$key] = count($collapsed);
            $collapsed[] = $row;
        }

        return $collapsed;
    }

    /**
     * Attach compact, independently translated payloads for every real
     * product in the family. Visitor state is applied per product, not copied
     * from the colour that happened to represent the card first.
     *
     * @param array<string, mixed> $product
     * @param array<int, int|string> $savedIds flipped product-id map
     * @param array<int, int|string> $boxIds flipped product-id map
     * @return array<string, mixed>
     */
    public function attach(array $product, string $locale, array $savedIds = [], array $boxIds = []): array
    {
        $id = (int) ($product['id'] ?? 0);
        $family = $this->memberships()[$id] ?? [];
        if (count($family) < 2) {
            return $product;
        }

        $models = $this->models($family);
        $wasFeatured = !empty($product['is_featured']);
        $familyFeatured = $wasFeatured;
        foreach ($family as $memberId) {
            if (isset($models[$memberId]) && ProductVisibility::isPublic($models[$memberId]) && !empty($models[$memberId]->is_featured)) {
                $familyFeatured = true;
                break;
            }
        }
        if ($familyFeatured) {
            $product['is_featured'] = true;
            if (!$wasFeatured && isset($product['card'])) {
                $product['card'] = ProductCardPresenter::make($product, $locale);
            }
        }

        usort($family, static function (int $a, int $b) use ($id, $models): int {
            if ($a === $id xor $b === $id) {
                return $a === $id ? -1 : 1;
            }
            $fa = isset($models[$a]) && !empty($models[$a]->is_featured) ? 1 : 0;
            $fb = isset($models[$b]) && !empty($models[$b]->is_featured) ? 1 : 0;
            if ($fa !== $fb) {
                return $fb <=> $fa;
            }
            $sa = isset($models[$a]) ? (int) ($models[$a]->sort_order ?? PHP_INT_MAX) : PHP_INT_MAX;
            $sb = isset($models[$b]) ? (int) ($models[$b]->sort_order ?? PHP_INT_MAX) : PHP_INT_MAX;
            return ($sa <=> $sb) ?: ($a <=> $b);
        });

        $variants = [];
        $finishSlots = [];
        foreach ($family as $memberId) {
            if (!isset($models[$memberId])) {
                continue;
            }
            /* Hidden or media-incomplete colours must not leak through a
               visible sibling's same-card selector. Keep the graph intact for
               the remaining public members, but hydrate only openable items. */
            if (!ProductVisibility::isPublic($models[$memberId])) {
                continue;
            }

            $payload = $models[$memberId]->translate($locale);
            if ($familyFeatured) {
                $payload['is_featured'] = true;
            }
            $card = ProductCardPresenter::make($payload, $locale);
            $rawName = trim((string) ($payload['name'] ?? ''));
            $name = $rawName !== '' ? $rawName : trans('products.name_pending', [], $locale);

            $labels = [];
            foreach ((array) ($card['finishes'] ?? []) as $finish) {
                $label = trim((string) ($finish['label'] ?? ''));
                if ($label !== '' && !in_array($label, $labels, true)) {
                    $labels[] = $label;
                }
            }
            $firstFinish = $card['finishes'][0] ?? null;
            $finishKey = (string) ($firstFinish['key'] ?? 'neutral');
            $finishLabel = $labels !== [] ? implode(' / ', $labels) : trans('products.finish_unspecified', [], $locale);
            $payload['card'] = $card;
            $payload['display_name'] = $name;
            $translatedRoute = app(\Core\Localization\Translator::class)->trans('routes.products.show', [], $locale);
            $slugVal = rawurlencode((string) ($payload['slug'] ?? ''));
            $payload['url'] = url('/' . $locale . '/' . str_replace('{slug}', $slugVal, ltrim($translatedRoute, '/')));
            $payload['finish'] = [
                'key' => $finishKey,
                'label' => $finishLabel,
            ];
            $payload['is_saved'] = isset($savedIds[$memberId]);
            $payload['in_box'] = isset($boxIds[$memberId]);

            $compact = $this->compact($payload);
            $rank = ($memberId === $id ? 4 : 0) + ($rawName !== '' ? 2 : 0) + (trim((string) ($payload['description'] ?? '')) !== '' ? 1 : 0);
            $signature = $labels !== []
                ? strtolower($finishKey . '|' . $finishLabel)
                : 'member-' . $memberId;

            if (isset($finishSlots[$signature])) {
                $existingSlot = $finishSlots[$signature]['slot'];
                if ($rank > $finishSlots[$signature]['rank']) {
                    $variants[$existingSlot] = $compact;
                    $finishSlots[$signature]['rank'] = $rank;
                }
                continue;
            }

            $finishSlots[$signature] = ['slot' => count($variants), 'rank' => $rank];
            $variants[] = $compact;
        }

        if (count($variants) > 1) {
            $product['color_variants'] = $variants;
        }

        return $product;
    }

    /**
     * The full membership map (product id => ordered family member ids).
     * The admin grid collapses families exactly like the storefront.
     *
     * @return array<int, array<int, int>>
     */
    public function familyMap(): array
    {
        return $this->memberships();
    }

    /** @return int[] the family of one product (itself when it has no colours) */
    public function familyOf(int $productId): array
    {
        return $this->memberships()[$productId] ?? [$productId];
    }

    /** @return array<int, bool> */
    private function namedIds(): array
    {
        if ($this->namedIds !== null) {
            return $this->namedIds;
        }

        $rows = Product::query()->connection()->select(
            "SELECT DISTINCT pt.product_id
             FROM product_translations pt
             WHERE TRIM(COALESCE(pt.name, '')) <> ''",
        );

        $map = [];
        foreach ($rows as $row) {
            $pid = (int) ($row['product_id'] ?? 0);
            if ($pid > 0) {
                $map[$pid] = true;
            }
        }

        return $this->namedIds = $map;
    }

    /** @return array<int, array<int, int>> */
    private function memberships(): array
    {
        if ($this->memberships !== null) {
            return $this->memberships;
        }

        $rows = Product::query()->connection()->select(
            "SELECT pr.product_id, pr.related_product_id
             FROM product_relations pr
             INNER JOIN products p ON p.id = pr.product_id AND p.deleted_at IS NULL
             INNER JOIN products rp ON rp.id = pr.related_product_id AND rp.deleted_at IS NULL
             WHERE pr.relation_type = 'color_variant'",
        );

        $adjacency = [];
        foreach ($rows as $row) {
            $a = (int) $row['product_id'];
            $b = (int) $row['related_product_id'];
            if ($a <= 0 || $b <= 0 || $a === $b) {
                continue;
            }
            $adjacency[$a][$b] = true;
            $adjacency[$b][$a] = true;
        }

        $map = [];
        $visited = [];
        foreach (array_keys($adjacency) as $start) {
            if (isset($visited[$start])) {
                continue;
            }
            $stack = [$start];
            $members = [];
            while ($stack !== []) {
                $id = array_pop($stack);
                if (isset($visited[$id])) {
                    continue;
                }
                $visited[$id] = true;
                $members[] = $id;
                foreach (array_keys($adjacency[$id] ?? []) as $next) {
                    if (!isset($visited[$next])) {
                        $stack[] = $next;
                    }
                }
            }
            if (count($members) < 2) {
                continue;
            }
            sort($members, SORT_NUMERIC);
            foreach ($members as $id) {
                $map[$id] = $members;
            }
        }

        return $this->memberships = $map;
    }

    /**
     * @param array<int, int> $ids
     * @return array<int, Product>
     */
    private function models(array $ids): array
    {
        $missing = array_values(array_diff($ids, array_keys($this->models)));
        if ($missing !== []) {
            $loaded = Product::query()->whereIn('id', $missing)->get();
            Product::eagerLoad($loaded);
            foreach ($loaded as $model) {
                if ($model instanceof Product) {
                    $this->models[(int) $model->getKey()] = $model;
                }
            }
        }

        $ordered = [];
        foreach ($ids as $id) {
            if (isset($this->models[$id])) {
                $ordered[$id] = $this->models[$id];
            }
        }

        return $ordered;
    }

    /**
     * The selector needs every card-facing field, but not another recursive
     * copy of the family or unrelated admin relations.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function compact(array $payload): array
    {
        $keys = [
            'id', 'slug', 'url', 'name', 'display_name', 'sku', 'model_code',
            'description', 'short_description', 'image', 'card_slides',
            'price', 'currency', 'status', 'is_featured', 'is_new', 'card', 'finish',
            'is_saved', 'in_box',
        ];

        return array_intersect_key($payload, array_flip($keys));
    }
}
