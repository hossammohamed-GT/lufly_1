<?php

declare(strict_types=1);

namespace Database\Seeders;

use Core\Database\Schema\SchemaBuilder;
use Core\Database\Seeding\Seeder;

/**
 * Synchronizes feature presets, 4–6 per-product card features, trilingual
 * (en / tr / cs) product + specification + SEO translations, and WC <-> Toilet
 * Seat Cover companion links from database/seeders/data/product-enrichment.json.
 */
class ProductEnrichmentSeeder extends Seeder
{
    public function run(): void
    {
        $file = __DIR__ . '/data/product-enrichment.json';
        if (!is_file($file)) {
            return;
        }

        $schema = new SchemaBuilder($this->db);
        if (!$schema->hasTable('product_feature_presets') || !$schema->hasTable('product_card_features')) {
            return;
        }

        $payload = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($payload)) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        /* 1. Ready-made feature presets + translations */
        $presetIdByKey = [];
        foreach (($payload['presets'] ?? []) as $preset) {
            if (!is_array($preset)) {
                continue;
            }
            $presetKey = trim((string) ($preset['preset_key'] ?? ''));
            if ($presetKey === '') {
                continue;
            }

            $existing = $this->db->table('product_feature_presets')->where('preset_key', $presetKey)->first();
            if ($existing !== null) {
                $presetId = (int) $existing['id'];
            } else {
                $presetId = (int) $this->db->insert('product_feature_presets', [
                    'preset_key' => $presetKey,
                    'icon_key' => (string) ($preset['icon_key'] ?? 'spark'),
                    'category_hint' => $preset['category_hint'] ?? null,
                    'sort_order' => (int) ($preset['sort_order'] ?? 0),
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            $presetIdByKey[$presetKey] = $presetId;

            foreach (($preset['translations'] ?? []) as $locale => $pair) {
                if (!is_array($pair) || count($pair) < 2) {
                    continue;
                }
                $existsTr = $this->db->selectOne(
                    'SELECT id FROM product_feature_preset_translations WHERE preset_id = ? AND locale = ?',
                    [$presetId, (string) $locale],
                );
                if ($existsTr === null) {
                    $this->db->insert('product_feature_preset_translations', [
                        'preset_id' => $presetId,
                        'locale' => (string) $locale,
                        'label' => trim((string) $pair[0]),
                        'placeholder_value' => trim((string) $pair[1]),
                    ]);
                }
            }
        }

        /* 2. Product map */
        $productsBySlug = [];
        foreach ($this->db->select('SELECT id, slug FROM products') as $pRow) {
            $productsBySlug[(string) $pRow['slug']] = (int) $pRow['id'];
        }

        /* 3. Per-product translations + 4–6 card features */
        foreach (($payload['products'] ?? []) as $slug => $entry) {
            $productId = $productsBySlug[(string) $slug] ?? null;
            if ($productId === null || !is_array($entry)) {
                continue;
            }

            foreach (($entry['translations'] ?? []) as $locale => $trData) {
                if (!is_array($trData)) {
                    continue;
                }
                $existingPt = $this->db->selectOne(
                    'SELECT id FROM product_translations WHERE product_id = ? AND locale = ?',
                    [$productId, (string) $locale],
                );
                if ($existingPt !== null) {
                    $this->db->table('product_translations')->where('id', (int) $existingPt['id'])->update([
                        'name' => (string) ($trData['name'] ?? ''),
                        'short_description' => $trData['short_description'] ?? null,
                        'description' => $trData['description'] ?? null,
                    ]);
                } else {
                    $this->db->insert('product_translations', [
                        'product_id' => $productId,
                        'locale' => (string) $locale,
                        'name' => (string) ($trData['name'] ?? ''),
                        'short_description' => $trData['short_description'] ?? null,
                        'description' => $trData['description'] ?? null,
                    ]);
                }
            }

            $hasFeatures = $this->db->selectOne(
                'SELECT id FROM product_card_features WHERE product_id = ? LIMIT 1',
                [$productId],
            );
            if ($hasFeatures === null) {
                foreach (($entry['features'] ?? []) as $feat) {
                    if (!is_array($feat)) {
                        continue;
                    }
                    $pKey = (string) ($feat['preset_key'] ?? '');
                    $featureId = (int) $this->db->insert('product_card_features', [
                        'product_id' => $productId,
                        'preset_id' => $presetIdByKey[$pKey] ?? null,
                        'icon_key' => (string) ($feat['icon_key'] ?? 'spark'),
                        'sort_order' => (int) ($feat['sort_order'] ?? 0),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    foreach (($feat['translations'] ?? []) as $locale => $fTr) {
                        if (!is_array($fTr)) {
                            continue;
                        }
                        $this->db->insert('product_card_feature_translations', [
                            'feature_id' => $featureId,
                            'locale' => (string) $locale,
                            'label' => (string) ($fTr['label'] ?? ''),
                            'feature_value' => (string) ($fTr['value'] ?? ''),
                        ]);
                    }
                }
            }

            /* Authoritative product finish/color specification + trilingual translations */
            if (is_array($entry['finish'] ?? null) && trim((string) ($entry['finish']['en'] ?? '')) !== '') {
                $finishEn = trim((string) $entry['finish']['en']);
                $finishTr = trim((string) ($entry['finish']['tr'] ?? $finishEn));
                $finishCs = trim((string) ($entry['finish']['cs'] ?? $finishEn));

                $existingSpec = $this->db->selectOne(
                    "SELECT id FROM product_specifications WHERE product_id = ? AND variant_id IS NULL AND LOWER(spec_key) IN ('finish', 'color', 'colour') ORDER BY id ASC LIMIT 1",
                    [$productId],
                );
                if ($existingSpec !== null) {
                    $specId = (int) $existingSpec['id'];
                    $this->db->table('product_specifications')->where('id', $specId)->update([
                        'spec_key' => 'Finish',
                        'spec_value' => mb_substr($finishEn, 0, 255),
                    ]);
                } else {
                    $maxSortRow = $this->db->selectOne(
                        'SELECT COALESCE(MAX(sort_order), 0) AS max_sort FROM product_specifications WHERE product_id = ?',
                        [$productId],
                    );
                    $specId = (int) $this->db->insert('product_specifications', [
                        'product_id' => $productId,
                        'variant_id' => null,
                        'spec_key' => 'Finish',
                        'spec_value' => mb_substr($finishEn, 0, 255),
                        'unit' => null,
                        'sort_order' => ((int) ($maxSortRow['max_sort'] ?? 0)) + 1,
                    ]);
                }

                if ($schema->hasTable('product_specification_translations')) {
                    $specTrRows = [
                        'en' => ['Finish', $finishEn],
                        'tr' => ['Yüzey Kaplaması', $finishTr],
                        'cs' => ['Povrchová úprava', $finishCs],
                    ];
                    foreach ($specTrRows as $loc => [$locKey, $locVal]) {
                        $existingSt = $this->db->selectOne(
                            'SELECT id FROM product_specification_translations WHERE specification_id = ? AND locale = ?',
                            [$specId, $loc],
                        );
                        if ($existingSt !== null) {
                            $this->db->table('product_specification_translations')->where('id', (int) $existingSt['id'])->update([
                                'spec_key' => $locKey,
                                'spec_value' => mb_substr($locVal, 0, 255),
                            ]);
                        } else {
                            $this->db->insert('product_specification_translations', [
                                'specification_id' => $specId,
                                'locale' => $loc,
                                'spec_key' => $locKey,
                                'spec_value' => mb_substr($locVal, 0, 255),
                            ]);
                        }
                    }
                }
            }
        }

        /* 4. WC -> Toilet Seat Cover companion links */
        $this->db->table('product_relations')->where('relation_type', 'seat_cover')->delete();
        foreach (($payload['wc_seat_links'] ?? []) as $link) {
            if (!is_array($link)) {
                continue;
            }
            $wcId = $productsBySlug[(string) ($link['wc_slug'] ?? '')] ?? null;
            $seatId = $productsBySlug[(string) ($link['seat_slug'] ?? '')] ?? null;
            if ($wcId !== null && $seatId !== null) {
                $this->db->insert('product_relations', [
                    'product_id' => $wcId,
                    'related_product_id' => $seatId,
                    'relation_type' => 'seat_cover',
                ]);
            }
        }

        /* 5. Initial featured & priority showcase (applied only when Admin has
           not customized featured items yet, preserving future Admin overrides). */
        $hasAdminFeatured = $this->db->selectOne(
            'SELECT id FROM products WHERE is_featured = 1 AND deleted_at IS NULL LIMIT 1',
        );
        if ($hasAdminFeatured === null) {
            foreach (($payload['featured_priority'] ?? []) as $featItem) {
                if (!is_array($featItem)) {
                    continue;
                }
                $fSlug = (string) ($featItem['slug'] ?? '');
                $fPid = $productsBySlug[$fSlug] ?? null;
                if ($fPid !== null) {
                    $this->db->table('products')->where('id', $fPid)->update([
                        'is_featured' => 1,
                        'sort_order' => (int) ($featItem['sort_order'] ?? 1),
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }
}
