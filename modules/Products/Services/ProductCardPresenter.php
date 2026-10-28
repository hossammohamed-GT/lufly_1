<?php

declare(strict_types=1);

namespace Modules\Products\Services;

/**
 * Builds the truthful, localized summary shown on a catalog card.
 *
 * Per-product quick specifications / features come from `product_card_features`
 * (4 to 6 rows per product, backed by `product_feature_presets` and
 * `ProductFeatureIconCatalog`). When a newly created product has fewer than
 * 4 stored rows, conservative fallbacks keep every card uniformly balanced
 * with at least 4 localized items.
 */
final class ProductCardPresenter
{
    /** @var array<string, array{0: string, 1: string}> phrase => swatch, canonical English label */
    private const FINISHES = [
        'satin chrome brushed' => ['chrome', 'Brushed Chrome'],
        'chrome brushed' => ['chrome', 'Brushed Chrome'],
        'brushed chrome' => ['chrome', 'Brushed Chrome'],
        'chrome shinning' => ['chrome', 'Chrome'],
        'chrome shining' => ['chrome', 'Chrome'],
        'shinning chrome' => ['chrome', 'Chrome'],
        'shining chrome' => ['chrome', 'Chrome'],
        'mirror polished' => ['chrome', 'Mirror Polished'],
        'mirror chrome' => ['chrome', 'Chrome'],
        'brushed bronze' => ['bronze', 'Brushed Bronze'],
        'soft bronze' => ['bronze', 'Bronze'],
        'brush rose gold' => ['rose-gold', 'Brushed Rose Gold'],
        'gold matt' => ['gold', 'Matt Gold'],
        'gold matte' => ['gold', 'Matt Gold'],
        'brush gold' => ['gold', 'Brushed Gold'],
        'brish gold' => ['gold', 'Brushed Gold'],
        'gun metal' => ['gunmetal', 'Gunmetal'],
        'metal gun' => ['gunmetal', 'Gunmetal'],
        'gold bronze' => ['bronze', 'Gold Bronze'],
        'chrome nickel' => ['nickel', 'Chrome Nickel'],
        'chrome nickle' => ['nickel', 'Chrome Nickel'],
        'royal blue' => ['royal-blue', 'Royal Blue'],
        'dark blue' => ['dark-blue', 'Dark Blue'],
        'light blue' => ['light-blue', 'Light Blue'],
        'brushed stainless steel' => ['steel', 'Brushed Stainless Steel'],
        'stainless steel' => ['steel', 'Stainless Steel'],
        'brushed rose gold' => ['rose-gold', 'Brushed Rose Gold'],
        'rose gold' => ['rose-gold', 'Rose Gold'],
        'rosegold' => ['rose-gold', 'Rose Gold'],
        'roze gold' => ['rose-gold', 'Rose Gold'],
        'rose gols' => ['rose-gold', 'Rose Gold'],
        'roze glold' => ['rose-gold', 'Rose Gold'],
        'brushed gold' => ['gold', 'Brushed Gold'],
        'brass nickel' => ['nickel', 'Brass Nickel'],
        'brass nickle' => ['nickel', 'Brass Nickel'],
        'brushed nickel' => ['nickel', 'Brushed Nickel'],
        'brushed nickle' => ['nickel', 'Brushed Nickel'],
        'brush nickel' => ['nickel', 'Brushed Nickel'],
        'brush nickle' => ['nickel', 'Brushed Nickel'],
        'brished nickel' => ['nickel', 'Brushed Nickel'],
        'brished nickle' => ['nickel', 'Brushed Nickel'],
        'polished chrome' => ['chrome', 'Polished Chrome'],
        'polish chrome' => ['chrome', 'Polished Chrome'],
        'brushed gunmetal' => ['gunmetal', 'Brushed Gunmetal'],
        'gungray' => ['gunmetal', 'Gun Grey'],
        'gun gray' => ['gunmetal', 'Gun Grey'],
        'gun grey' => ['gunmetal', 'Gun Grey'],
        'brass bronze' => ['bronze', 'Brass Bronze'],
        'alpine white' => ['white', 'Alpine White'],
        'matte white' => ['white', 'Matte White'],
        'matt white' => ['white', 'Matt White'],
        'matte black' => ['black', 'Matte Black'],
        'matt black' => ['black', 'Matt Black'],
        'chrome' => ['chrome', 'Chrome'],
        'chrom' => ['chrome', 'Chrome'],
        'nickel' => ['nickel', 'Nickel'],
        'golden' => ['gold', 'Gold'],
        'gold' => ['gold', 'Gold'],
        'steel' => ['steel', 'Steel'],
        'bronze' => ['bronze', 'Bronze'],
        'gunmetal' => ['gunmetal', 'Gunmetal'],
        'white' => ['white', 'White'],
        'black' => ['black', 'Black'],
        'pink' => ['pink', 'Pink'],
        'blue' => ['blue', 'Blue'],
        'green' => ['green', 'Green'],
        'yellow' => ['yellow', 'Yellow'],
        'ceyan' => ['cyan', 'Cyan'],
        'cyan' => ['cyan', 'Cyan'],
        'grey' => ['gray', 'Grey'],
        'gray' => ['gray', 'Gray'],
        'beige' => ['beige', 'Beige'],
        'polished' => ['chrome', 'Polished'],
        'shinning' => ['chrome', 'Polished'],
        'shining' => ['chrome', 'Polished'],
        'brushed' => ['steel', 'Brushed'],
        'prushed' => ['steel', 'Brushed'],
        'satin' => ['steel', 'Satin'],
    ];

    /**
     * Exact per-locale translations for every canonical finish label so colour
     * swatches and piece-finish captions stay 1-to-1 distinct in en, tr, and cs.
     *
     * @var array<string, array{tr: string, cs: string}>
     */
    private const FINISH_TRANSLATIONS = [
        'Brushed Chrome' => ['tr' => 'Fırçalı Krom', 'cs' => 'Kartáčovaný chrom'],
        'Chrome' => ['tr' => 'Krom', 'cs' => 'Chrom'],
        'Polished Chrome' => ['tr' => 'Parlak Krom', 'cs' => 'Lesklý chrom'],
        'Mirror Polished' => ['tr' => 'Ayna Parlak', 'cs' => 'Zrcadlový lesk'],
        'Polished' => ['tr' => 'Parlak', 'cs' => 'Leštěný'],
        'Brushed Bronze' => ['tr' => 'Fırçalı Bronz', 'cs' => 'Kartáčovaný bronz'],
        'Gold Bronze' => ['tr' => 'Altın Bronz', 'cs' => 'Zlatý bronz'],
        'Brass Bronze' => ['tr' => 'Pirinç Bronz', 'cs' => 'Mosazný bronz'],
        'Bronze' => ['tr' => 'Bronz', 'cs' => 'Bronzová'],
        'Brushed Rose Gold' => ['tr' => 'Fırçalı Roz Altın', 'cs' => 'Kartáčované růžové zlato'],
        'Rose Gold' => ['tr' => 'Roz Altın', 'cs' => 'Růžové zlato'],
        'Matt Gold' => ['tr' => 'Mat Altın', 'cs' => 'Matné zlato'],
        'Brushed Gold' => ['tr' => 'Fırçalı Altın', 'cs' => 'Kartáčované zlato'],
        'Gold' => ['tr' => 'Altın', 'cs' => 'Zlatá'],
        'Brushed Gunmetal' => ['tr' => 'Fırçalı Gunmetal', 'cs' => 'Kartáčovaný gunmetal'],
        'Gun Grey' => ['tr' => 'Gunmetal Gri', 'cs' => 'Gunmetal šedá'],
        'Gunmetal' => ['tr' => 'Gunmetal', 'cs' => 'Gunmetal'],
        'Chrome Nickel' => ['tr' => 'Krom Nikel', 'cs' => 'Chrom-nikl'],
        'Brass Nickel' => ['tr' => 'Pirinç Nikel', 'cs' => 'Mosaz-nikl'],
        'Brushed Nickel' => ['tr' => 'Fırçalı Nikel', 'cs' => 'Kartáčovaný nikl'],
        'Nickel' => ['tr' => 'Nikel', 'cs' => 'Nikl'],
        'Royal Blue' => ['tr' => 'Kraliyet Mavisi', 'cs' => 'Královská modrá'],
        'Dark Blue' => ['tr' => 'Lacivert', 'cs' => 'Tmavě modrá'],
        'Light Blue' => ['tr' => 'Açık Mavi', 'cs' => 'Světle modrá'],
        'Blue' => ['tr' => 'Mavi', 'cs' => 'Modrá'],
        'Brushed Stainless Steel' => ['tr' => 'Fırçalı Paslanmaz Çelik', 'cs' => 'Kartáčovaná nerezová ocel'],
        'Stainless Steel' => ['tr' => 'Paslanmaz Çelik', 'cs' => 'Nerezová ocel'],
        'Steel' => ['tr' => 'Çelik', 'cs' => 'Ocel'],
        'Brushed' => ['tr' => 'Fırçalı', 'cs' => 'Kartáčovaný'],
        'Satin' => ['tr' => 'Saten', 'cs' => 'Saténový'],
        'Alpine White' => ['tr' => 'Alp Beyazı', 'cs' => 'Alpská bílá'],
        'Matte White' => ['tr' => 'Mat Beyaz', 'cs' => 'Matná bílá'],
        'Matt White' => ['tr' => 'Mat Beyaz', 'cs' => 'Matná bílá'],
        'White' => ['tr' => 'Beyaz', 'cs' => 'Bílá'],
        'Matte Black' => ['tr' => 'Mat Siyah', 'cs' => 'Matná černá'],
        'Matt Black' => ['tr' => 'Mat Siyah', 'cs' => 'Matná černá'],
        'Black' => ['tr' => 'Siyah', 'cs' => 'Černá'],
        'Pink' => ['tr' => 'Pembe', 'cs' => 'Růžová'],
        'Green' => ['tr' => 'Yeşil', 'cs' => 'Zelená'],
        'Yellow' => ['tr' => 'Sarı', 'cs' => 'Žlutá'],
        'Cyan' => ['tr' => 'Turkuaz', 'cs' => 'Tyrkysová'],
        'Grey' => ['tr' => 'Gri', 'cs' => 'Šedá'],
        'Gray' => ['tr' => 'Gri', 'cs' => 'Šedá'],
        'Beige' => ['tr' => 'Bej', 'cs' => 'Béžová'],
    ];

    /** @param array<string, mixed> $product translated product payload */
    public static function make(array $product, ?string $locale = null): array
    {
        $resolvedLocale = $locale ?? (isset($product['locale']) && is_string($product['locale']) && $product['locale'] !== ''
            ? $product['locale']
            : null);

        $sourceDescription = self::sourceDescription($product);
        $specs = is_array($product['specs'] ?? null) ? $product['specs'] : [];
        $variants = is_array($product['variants'] ?? null) ? $product['variants'] : [];

        $material = self::material($specs, $sourceDescription, $resolvedLocale);
        $installation = self::installation($specs, $sourceDescription, $resolvedLocale);
        $dimensions = self::dimensions($product['dimensions'] ?? null, $specs, $sourceDescription);
        $hasCartridge = preg_match('/\b(?:ceramic\s+cartridge|valve\s+core\s+material\s*[:\-]?\s*ceramic)\b/iu', $sourceDescription) === 1;
        $finishes = self::finishes($variants, $specs, $sourceDescription, $resolvedLocale);

        $summary = self::cardSpecs(
            $product,
            $dimensions,
            $material,
            $installation,
            $hasCartridge,
            $finishes,
            $resolvedLocale,
        );

        $price = (float) ($product['price'] ?? 0);
        $hasPrice = $price > 0;
        $name = trim((string) ($product['name'] ?? ''));
        if ($name === '') {
            $name = trans('products.name_pending', [], $resolvedLocale);
        }
        $isFeatured = !empty($product['is_featured']);
        $status = (string) ($product['status'] ?? '');
        $badge = $status === 'coming_soon'
            ? trans('common.coming_soon', [], $resolvedLocale)
            : ($isFeatured ? trans('common.featured', [], $resolvedLocale) : '');

        return [
            'is_featured' => $isFeatured,
            'badge' => $badge,
            'features' => self::features($specs, $sourceDescription, $installation, $resolvedLocale),
            'finishes' => array_slice($finishes, 0, 4),
            'finishes_label' => count($finishes) === 1
                ? trans('products.finishes_one', [], $resolvedLocale)
                : trans('products.finishes_many', ['n' => count($finishes)], $resolvedLocale),
            'available_finishes_label' => trans('products.available_finishes', [], $resolvedLocale),
            'specs' => array_slice($summary, 0, 6),
            'has_price' => $hasPrice,
            'cart_enabled' => $hasPrice,
            'price_caption' => trans('products.price_label', [], $resolvedLocale),
            'price_label' => $hasPrice ? self::priceLabel($price, (string) ($product['currency'] ?? '')) : trans('products.price_pending', [], $resolvedLocale),
            'cart_label' => $hasPrice ? trans('products.add_to_cart', [], $resolvedLocale) : trans('products.cart_pending', [], $resolvedLocale),
            'save_label' => trans('favorites.save', [], $resolvedLocale),
            'saved_label' => trans('favorites.saved', [], $resolvedLocale),
            'favorite_added' => trans('favorites.added_named', ['name' => $name], $resolvedLocale),
            'favorite_removed' => trans('favorites.removed_named', ['name' => $name], $resolvedLocale),
            'box_label_on' => trans('box.added', [], $resolvedLocale),
            'box_label_off' => trans('products.add_to_cart', [], $resolvedLocale),
            'box_error' => trans('box.err', [], $resolvedLocale),
        ];
    }

    /**
     * Build 4 to 6 compact specification/feature rows per product.
     * Database-backed `card_features` rows always take precedence; fallback rows
     * ensure even a newly created product never renders fewer than 4 items.
     *
     * @param array<string, mixed> $product
     * @param array<int, array{key: string, label: string}> $finishes
     * @return array<int, array{icon: string, label: string, value: string}>
     */
    private static function cardSpecs(
        array $product,
        string $dimensions,
        string $material,
        string $installation,
        bool $hasCartridge,
        array $finishes,
        ?string $locale,
    ): array {
        $items = [];
        $seenLabels = [];
        $push = static function (string $icon, string $label, string $value) use (&$items, &$seenLabels): void {
            $label = trim($label);
            $value = trim($value);
            if ($label === '' || $value === '' || count($items) >= 6) {
                return;
            }
            $norm = mb_strtolower($label, 'UTF-8');
            if (isset($seenLabels[$norm])) {
                return;
            }
            $seenLabels[$norm] = true;
            $items[] = [
                'icon' => ProductFeatureIconCatalog::normalize($icon),
                'label' => $label,
                'value' => $value,
            ];
        };

        $dbFeatures = is_array($product['card_features'] ?? null) ? $product['card_features'] : [];
        foreach ($dbFeatures as $row) {
            if (!is_array($row)) {
                continue;
            }
            $push(
                (string) ($row['icon'] ?? 'spec'),
                (string) ($row['label'] ?? ''),
                (string) ($row['value'] ?? ''),
            );
        }

        if (count($items) >= 4) {
            return array_slice($items, 0, 6);
        }

        /* Fallback backfill for brand-new products without 4 stored rows yet */
        if ($dimensions !== '') {
            $push('dimensions', trans('products.dimensions', [], $locale), $dimensions);
        }
        if ($material !== '') {
            $push('material', trans('products.material_label', [], $locale), $material);
        }
        if ($installation !== '') {
            $push('installation', trans('products.installation_label', [], $locale), $installation);
        }
        if ($hasCartridge) {
            $push('cartridge', trans('products.cartridge_label', [], $locale), trans('products.feat_ceramic_cartridge', [], $locale));
        }
        if ($finishes !== []) {
            $finishLabels = array_values(array_filter(array_map(
                static fn (array $f): string => trim((string) ($f['label'] ?? '')),
                $finishes,
            )));
            if ($finishLabels !== []) {
                $push('finish', trans('products.finish_label', [], $locale), implode(' / ', array_slice($finishLabels, 0, 2)));
            }
        }
        $categoryName = trim((string) ($product['category_name'] ?? ''));
        if ($categoryName !== '') {
            $push('spec', trans('products.category_label', [], $locale), $categoryName);
        }
        $modelCode = trim((string) ($product['model_code'] ?? $product['sku'] ?? ''));
        if ($modelCode !== '') {
            $push('spec', trans('products.code_label', [], $locale), $modelCode);
        }
        $push('certification', trans('products.standard_label', [], $locale), trans('products.standard_en_ce', [], $locale));
        $push('warranty', trans('products.guarantee_title', [], $locale), 'LUFLY');

        return array_slice($items, 0, 6);
    }

    private static function sourceDescription(array $product): string
    {
        $source = trim((string) ($product['source_description'] ?? ''));
        if ($source === '') {
            $source = trim((string) ($product['short_description'] ?? '') . ' ' . (string) ($product['description'] ?? ''));
        }

        return trim((string) preg_replace('/[^\S\r\n]+/u', ' ', strip_tags($source)));
    }

    private static function priceLabel(float $price, string $currency): string
    {
        $decimals = abs($price - round($price)) > 0.0001 ? 2 : 0;
        $label = number_format($price, $decimals);
        $currency = strtoupper(trim($currency));

        return preg_match('/^[A-Z]{3}$/', $currency) === 1 ? $label . ' ' . $currency : $label;
    }

    /** @return array<int, array{icon: string, label: string}> */
    private static function features(array $specs, string $description, string $installation, ?string $locale): array
    {
        $features = [];
        $seen = [];
        $add = static function (string $icon, string $label) use (&$features, &$seen): void {
            $label = trim($label);
            $key = mb_strtolower($label, 'UTF-8');
            if ($label === '' || isset($seen[$key]) || count($features) >= 3) {
                return;
            }
            $seen[$key] = true;
            $features[] = ['icon' => ProductFeatureIconCatalog::normalize($icon), 'label' => $label];
        };

        if ($installation !== '') {
            $add('installation', $installation);
        }

        $rules = [
            ['rimless', 'products.feat_rimless', '/\brimless\b/iu'],
            ['soft-close', 'products.feat_soft_close', '/\b(?:soft|slow)[ -]?clos(?:e|ing)\b/iu'],
            ['water-saving', 'products.feat_water_saving', '/\bwater[ -]?saving\b/iu'],
            ['easy-clean', 'products.feat_easy_clean', '/\beasy[ -]?clean\b/iu'],
            ['scratch-resistant', 'products.feat_scratch_resistant', '/\bscratch[ -]?resistant\b/iu'],
            ['cartridge', 'products.feat_ceramic_cartridge', '/\bceramic\s+cartridge\b/iu'],
            ['single-lever', 'products.feat_single_lever', '/\bsingle[ -]?(?:lever|handle)\b/iu'],
            ['thermostatic', 'products.feat_thermostatic', '/\bthermostatic\b/iu'],
            ['shower', 'products.feat_rain_shower', '/\brain\s+shower\b/iu'],
            ['shower', 'products.feat_hand_shower', '/\bhand[ -]?(?:shower|spray)\b/iu'],
            ['high-flow', 'products.feat_high_flow', '/\bhigh[ -]?flow\b/iu'],
            ['anti-odor', 'products.feat_anti_odor', '/\banti[ -]?odou?r\b/iu'],
            ['spec', 'products.feat_click_clack', '/\bclick[ -]?clack\b/iu'],
            ['drainage', 'products.feat_with_overflow', '/\bwith\s+overflow\b/iu'],
            ['spec', 'products.feat_self_closing', '/\bself[ -]?closing\b/iu'],
            ['connection', 'products.feat_dual_outlet', '/\bdual[ -]?outlet\b/iu'],
        ];
        foreach ($rules as [$icon, $transKey, $pattern]) {
            if (preg_match($pattern, $description) === 1) {
                $add($icon, trans($transKey, [], $locale));
            }
        }

        if (count($features) < 3) {
            foreach ($specs as $row) {
                $key = (string) ($row['raw_key'] ?? $row['spec_key'] ?? '');
                $value = trim((string) ($row['spec_value'] ?? ''));
                if ($value === '' || preg_match('/^drawing\s*:|material|finish|colou?r|dimension|size|installation|mounting|cartridge|\btype\b/iu', $key) === 1) {
                    continue;
                }
                $add('spec', $value);
            }
        }

        return $features;
    }

    /** @return array<int, array{key: string, label: string}> */
    private static function finishes(array $variants, array $specs, string $description, ?string $locale): array
    {
        $finishes = [];
        $seen = [];
        $add = static function (string $value, bool $knownOnly = false) use (&$add, &$finishes, &$seen, $locale): void {
            $parts = preg_split('#\s*/\s*#u', $value) ?: [$value];
            if (count($parts) > 1) {
                $knownParts = [];
                foreach ($parts as $part) {
                    $candidate = self::finish((string) $part, $locale);
                    if ($candidate['key'] === 'neutral' || $candidate['label'] === '') {
                        $knownParts = [];
                        break;
                    }
                    $knownParts[] = $part;
                }
                if ($knownParts !== []) {
                    foreach ($knownParts as $part) {
                        $add((string) $part, $knownOnly);
                    }
                    return;
                }
            }

            $finish = self::finish($value, $locale);
            $key = mb_strtolower($finish['label'], 'UTF-8');
            if ($finish['label'] === '' || ($knownOnly && $finish['key'] === 'neutral') || isset($seen[$key])) {
                return;
            }
            $seen[$key] = true;
            $finishes[] = $finish;
        };

        foreach ($variants as $variant) {
            $name = trim((string) ($variant['variant_name'] ?? ''));
            if ($name !== '') {
                $add($name, true);
            }
        }
        foreach ($specs as $row) {
            $rawKey = (string) ($row['raw_key'] ?? $row['spec_key'] ?? '');
            if (preg_match('/finish|colou?r/iu', $rawKey) === 1) {
                $add((string) ($row['raw_value'] ?? $row['spec_value'] ?? ''));
            }
        }

        if (preg_match_all('/\b(?:finish|colou?r)\s*[:\-]\s*([^,;.\r\n]+(?:\s*,\s*with\s+(?:decorative\s+)?[^,;.\r\n]+)?)/iu', $description, $matches)) {
            foreach ($matches[1] as $value) {
                $parts = preg_split('/\s*,?\s+with\s+|\s*\+\s*/iu', (string) $value) ?: [(string) $value];
                foreach ($parts as $index => $part) {
                    $add((string) $part, $index > 0);
                }
            }
        }

        $phrases = array_keys(self::FINISHES);
        usort($phrases, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));
        $alternation = implode('|', array_map(
            static fn (string $phrase): string => preg_quote($phrase, '/'),
            $phrases,
        ));
        $suffix = '(?:\s+(?:pvd|shinning|shining|brushed|brush|brish|matt|matte))*';
        $pattern = '/\b(?:(?:pvd|shinning|shining|soft|mirror)\s+)*(' . $alternation . ')' . $suffix
            . '(?:\s*(?:\+|\bwith\b)\s*(?:pvd\s+)?(' . $alternation . ')' . $suffix . ')?'
            . '(?:\s+|-)(?:decorative\s+)?(?:colou?r(?:\s+finish)?|finish|accent)\b/iu';
        if (preg_match_all($pattern, $description, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $add((string) ($match[1] ?? ''));
                if (trim((string) ($match[2] ?? '')) !== '') {
                    $add((string) $match[2]);
                }
            }
        }
        if ($finishes === [] && preg_match('/\b(' . $alternation . ')\s+plated\b/iu', $description, $match) === 1) {
            $add((string) $match[1]);
        }

        $ssLabel = self::localizeFinish('Stainless Steel', $locale);
        $steelLabel = self::localizeFinish('Steel', $locale);
        $labels = array_column($finishes, 'label');
        if (in_array($ssLabel, $labels, true) && in_array($steelLabel, $labels, true)) {
            $finishes = array_values(array_filter(
                $finishes,
                static fn (array $finish): bool => $finish['label'] !== $steelLabel,
            ));
        }

        return $finishes;
    }

    /** @return array{key: string, label: string} */
    private static function finish(string $value, ?string $locale): array
    {
        $value = trim((string) preg_replace('/\s+/u', ' ', $value));
        $value = trim((string) preg_replace('/\s*\(\s*optional\s*\)\s*$/iu', '', $value));
        $value = trim((string) preg_replace('/^\d+\s*[-–]\s*/u', '', $value), " \t\n\r\0\x0B:-");
        if ($value === '' || strlen($value) > 72) {
            return ['key' => 'neutral', 'label' => ''];
        }

        foreach (self::FINISHES as $phrase => [$swatch, $label]) {
            if (preg_match('/\b' . preg_quote($phrase, '/') . '\b/iu', $value) === 1) {
                return ['key' => $swatch, 'label' => self::localizeFinish($label, $locale)];
            }
        }

        if (!preg_match('/\p{L}/u', $value)) {
            return ['key' => 'neutral', 'label' => ''];
        }

        return ['key' => 'neutral', 'label' => $value];
    }

    private static function localizeFinish(string $canonicalLabel, ?string $locale): string
    {
        $loc = $locale ?? app(\Core\Localization\Translator::class)->getLocale();
        if (in_array($loc, ['tr', 'cs'], true) && isset(self::FINISH_TRANSLATIONS[$canonicalLabel][$loc])) {
            return self::FINISH_TRANSLATIONS[$canonicalLabel][$loc];
        }

        return $canonicalLabel;
    }

    private static function material(array $specs, string $description, ?string $locale): string
    {
        $structured = self::specRawValue($specs, '/\bmaterials?\b/iu');
        if ($structured !== '') {
            return self::canonicalMaterial($structured, $locale);
        }

        if (preg_match('/\bstainless\s+steel(?:\s*(?:aisi\s*|#\s*)?304)?(?!\s*(?:screws?|fittings?|hoses?|braids?)\b)/iu', $description, $match)) {
            return self::canonicalMaterial((string) $match[0], $locale);
        }

        if (preg_match('/\b(vitreous\s+china|stainless\s+steel|ceramic|brass|acrylic|abs|pp|uf)\s+body\b/iu', $description, $match)) {
            return self::canonicalMaterial((string) $match[1], $locale);
        }

        $known = '(vitreous\s+china|stainless\s+steel(?:\s*(?:aisi\s*|#\s*)?304)?|ceramic|brass|acrylic|abs|pp|uf)';
        if (preg_match('/\bmaterials?\s*[:\-]?\s*' . $known . '/iu', $description, $match)) {
            return self::canonicalMaterial((string) $match[1], $locale);
        }
        if (preg_match('/\b(vitreous\s+china|acrylic)\b/iu', $description, $match)) {
            return self::canonicalMaterial((string) $match[1], $locale);
        }
        if (preg_match('/\bbrass\b(?=[^.]{0,30}\b(?:body|construction|material|chrome|plated|faucet)\b)/iu', $description)) {
            return trans('products.mat_brass', [], $locale);
        }
        if (preg_match('/\bceramic\b(?!\s+cartridge)/iu', $description)) {
            return trans('products.mat_ceramic', [], $locale);
        }
        if (preg_match('/\b(abs|pp|uf)\b/iu', $description, $match)) {
            return strtoupper((string) $match[1]);
        }

        return '';
    }

    private static function canonicalMaterial(string $value, ?string $locale): string
    {
        if (preg_match('/vitreous\s+china/iu', $value)) return trans('products.mat_vitreous_china', [], $locale);
        if (preg_match('/stainless\s+steel/iu', $value)) {
            return preg_match('/304/', $value)
                ? trans('products.mat_stainless_steel_304', [], $locale)
                : trans('products.mat_stainless_steel', [], $locale);
        }
        if (preg_match('/\bceramic\b/iu', $value)) return trans('products.mat_ceramic', [], $locale);
        if (preg_match('/\bbrass\b/iu', $value)) return trans('products.mat_brass', [], $locale);
        if (preg_match('/\bacrylic\b/iu', $value)) return trans('products.mat_acrylic', [], $locale);
        if (preg_match('/\b(?:abs|pp|uf)\b/iu', $value, $match)) return strtoupper((string) $match[0]);

        return trim($value);
    }

    private static function installation(array $specs, string $description, ?string $locale): string
    {
        $structured = self::specRawValue($specs, '/installation|mounting/iu');
        $source = $structured !== '' ? $structured : $description;
        $rules = [
            ['products.inst_wall_hung', '/\bwall[ -]?hung\b/iu'],
            ['products.inst_wall_mounted', '/\bwall[ -]?mounted\b/iu'],
            ['products.inst_floor_standing', '/\bfloor[ -]?standing\b/iu'],
            ['products.inst_floor_mounted', '/\bfloor[ -]?mounted\b/iu'],
            ['products.inst_deck_mounted', '/\bdeck[ -]?mounted\b/iu'],
            ['products.inst_countertop', '/\b(?:counter[ -]?top|over[ -]?top\s+basin)\b/iu'],
            ['products.inst_freestanding', '/\bfree[ -]?standing\b/iu'],
            ['products.inst_concealed', '/\bconcealed\b/iu'],
            ['products.inst_built_in', '/\bbuilt[ -]?in\b/iu'],
        ];
        foreach ($rules as [$transKey, $pattern]) {
            if (preg_match($pattern, $source) === 1) {
                return trans($transKey, [], $locale);
            }
        }

        return $structured;
    }

    private static function dimensions(mixed $dimensions, array $specs, string $description): string
    {
        if (is_array($dimensions)) {
            $values = [];
            foreach (['width_mm', 'height_mm', 'depth_mm'] as $key) {
                $value = (float) ($dimensions[$key] ?? 0);
                if ($value > 0) {
                    $values[] = self::number($value);
                }
            }
            if (count($values) >= 2) {
                return implode(' × ', $values) . ' mm';
            }
        }

        $drawing = [];
        foreach ($specs as $row) {
            $rawKey = trim((string) ($row['raw_key'] ?? $row['spec_key'] ?? ''));
            if (preg_match('/^drawing\s*:\s*(.+)$/iu', $rawKey) !== 1) {
                continue;
            }
            $localizedKey = trim((string) ($row['spec_key'] ?? $rawKey));
            $label = preg_replace('/^[^:]+\s*:\s*/u', '', $localizedKey) ?: $localizedKey;
            $value = trim((string) ($row['spec_value'] ?? ''));
            $unit = trim((string) ($row['unit'] ?? ''));
            if ($label === '' || $value === '') {
                continue;
            }
            $drawing[] = $label . ' ' . $value . ($unit !== '' ? ' ' . $unit : '');
            if (count($drawing) >= 3) {
                break;
            }
        }
        if ($drawing !== []) {
            return implode(' · ', $drawing);
        }

        $structured = self::specRawValue($specs, '/dimension|\bsize\b/iu');
        if ($structured !== '') {
            return $structured;
        }

        $number = '(\d+(?:[.,]\d+)?)';
        if (preg_match('/\b(?:overall\s+size|size|dimensions?)\s*[:\-]?\s*' . $number . '\s*(?:mm\s*)?[x×*]\s*' . $number . '\s*(?:mm\s*)?[x×*]\s*' . $number . '\s*mm\b/iu', $description, $match)) {
            return $match[1] . ' × ' . $match[2] . ' × ' . $match[3] . ' mm';
        }
        if (preg_match('/\b(?:overall\s+size|size|dimensions?)\s*[:\-]?\s*' . $number . '\s*(?:mm\s*)?[x×*]\s*' . $number . '\s*mm\b/iu', $description, $match)) {
            return $match[1] . ' × ' . $match[2] . ' mm';
        }

        return '';
    }

    private static function specRawValue(array $specs, string $keyPattern): string
    {
        foreach ($specs as $row) {
            $key = (string) ($row['raw_key'] ?? $row['spec_key'] ?? '');
            if (preg_match($keyPattern, $key) === 1) {
                $value = trim((string) ($row['raw_value'] ?? $row['spec_value'] ?? ''));
                $unit = trim((string) ($row['unit'] ?? ''));

                return $value . ($value !== '' && $unit !== '' ? ' ' . $unit : '');
            }
        }

        return '';
    }

    private static function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
