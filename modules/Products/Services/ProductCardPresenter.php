<?php

declare(strict_types=1);

namespace Modules\Products\Services;

/**
 * Builds the small, truthful summary shown on a catalog card.
 *
 * Structured variants/specifications always win. The legacy catalog stores
 * much of the same information in its editorial description, so conservative
 * parsers expose only explicit colours, dimensions and construction details;
 * they never infer a feature from a model-code suffix or invent a price.
 */
final class ProductCardPresenter
{
    /** @var array<string, array{0: string, 1: string}> phrase => swatch, label */
    private const FINISHES = [
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
        'brushed' => ['neutral', 'Brushed'],
        'prushed' => ['neutral', 'Brushed'],
        'satin' => ['neutral', 'Satin'],
    ];

    /** @param array<string, mixed> $product translated product payload */
    public static function make(array $product): array
    {
        $description = self::description($product);
        $specs = is_array($product['specs'] ?? null) ? $product['specs'] : [];
        $variants = is_array($product['variants'] ?? null) ? $product['variants'] : [];

        $material = self::material($specs, $description);
        $installation = self::installation($specs, $description);
        $dimensions = self::dimensions($product['dimensions'] ?? null, $specs, $description);
        $hasCartridge = preg_match('/\b(?:ceramic\s+cartridge|valve\s+core\s+material\s*[:\-]?\s*ceramic)\b/iu', $description) === 1;
        $finishes = self::finishes($variants, $specs, $description);

        $summary = [];
        if ($material !== '') {
            $summary[] = ['icon' => 'material', 'label' => trans('products.material_label'), 'value' => $material];
        }
        if ($hasCartridge) {
            $summary[] = ['icon' => 'cartridge', 'label' => trans('products.cartridge_label'), 'value' => 'Ceramic'];
        }
        if ($dimensions !== '') {
            $summary[] = ['icon' => 'dimensions', 'label' => trans('products.dimensions'), 'value' => $dimensions];
        }
        if ($installation !== '') {
            $summary[] = ['icon' => 'installation', 'label' => trans('products.installation_label'), 'value' => $installation];
        }

        $price = (float) ($product['price'] ?? 0);
        $hasPrice = $price > 0;
        $name = trim((string) ($product['name'] ?? ''));
        if ($name === '') {
            $name = trim((string) ($product['sku'] ?? ''));
        }
        if ($name === '') {
            $name = trim((string) ($product['model_code'] ?? ''));
        }
        $status = (string) ($product['status'] ?? '');
        $badge = $status === 'coming_soon'
            ? trans('common.coming_soon')
            : (!empty($product['is_featured']) ? trans('common.featured') : '');

        return [
            'badge' => $badge,
            'features' => self::features($specs, $description, $installation),
            'finishes' => array_slice($finishes, 0, 4),
            'finishes_label' => count($finishes) === 1
                ? trans('products.finishes_one')
                : trans('products.finishes_many', ['n' => count($finishes)]),
            'available_finishes_label' => trans('products.available_finishes'),
            'specs' => array_slice($summary, 0, 2),
            'has_price' => $hasPrice,
            'cart_enabled' => $hasPrice,
            'price_caption' => trans('products.price_label'),
            'price_label' => $hasPrice ? self::priceLabel($price, (string) ($product['currency'] ?? '')) : trans('products.price_pending'),
            'cart_label' => $hasPrice ? trans('products.add_to_cart') : trans('products.cart_pending'),
            'save_label' => trans('favorites.save'),
            'saved_label' => trans('favorites.saved'),
            'favorite_added' => trans('favorites.added_named', ['name' => $name]),
            'favorite_removed' => trans('favorites.removed_named', ['name' => $name]),
            'box_label_on' => trans('box.added'),
            'box_label_off' => trans('products.add_to_cart'),
            'box_error' => trans('box.err'),
        ];
    }

    private static function description(array $product): string
    {
        $text = trim((string) ($product['short_description'] ?? '') . ' ' . (string) ($product['description'] ?? ''));

        /* Keep line boundaries: "Finish: steel" must not swallow the next
           line's Length / Type / Installation attributes. */
        return trim((string) preg_replace('/[^\S\r\n]+/u', ' ', strip_tags($text)));
    }

    private static function priceLabel(float $price, string $currency): string
    {
        $decimals = abs($price - round($price)) > 0.0001 ? 2 : 0;
        $label = number_format($price, $decimals);
        $currency = strtoupper(trim($currency));

        return preg_match('/^[A-Z]{3}$/', $currency) === 1 ? $label . ' ' . $currency : $label;
    }

    /** @return array<int, array{icon: string, label: string}> */
    private static function features(array $specs, string $description, string $installation): array
    {
        $features = [];
        $seen = [];
        $add = static function (string $icon, string $label) use (&$features, &$seen): void {
            $label = trim($label);
            $key = strtolower($label);
            if ($label === '' || isset($seen[$key]) || count($features) >= 3) {
                return;
            }
            $seen[$key] = true;
            $features[] = ['icon' => $icon, 'label' => $label];
        };

        if ($installation !== '') {
            $add('installation', $installation);
        }

        $rules = [
            ['rimless', 'Rimless', '/\brimless\b/iu'],
            ['soft-close', 'Soft Close', '/\b(?:soft|slow)[ -]?clos(?:e|ing)\b/iu'],
            ['water-saving', 'Water Saving', '/\bwater[ -]?saving\b/iu'],
            ['easy-clean', 'Easy Clean', '/\beasy[ -]?clean\b/iu'],
            ['scratch-resistant', 'Scratch Resistant', '/\bscratch[ -]?resistant\b/iu'],
            ['cartridge', 'Ceramic Cartridge', '/\bceramic\s+cartridge\b/iu'],
            ['single-lever', 'Single Lever', '/\bsingle[ -]?(?:lever|handle)\b/iu'],
            ['thermostatic', 'Thermostatic', '/\bthermostatic\b/iu'],
            ['shower', 'Rain Shower', '/\brain\s+shower\b/iu'],
            ['shower', 'Hand Shower', '/\bhand[ -]?(?:shower|spray)\b/iu'],
            ['high-flow', 'High Flow', '/\bhigh[ -]?flow\b/iu'],
            ['anti-odor', 'Anti-Odor', '/\banti[ -]?odou?r\b/iu'],
            ['click-clack', 'Click-Clack', '/\bclick[ -]?clack\b/iu'],
            ['overflow', 'With Overflow', '/\bwith\s+overflow\b/iu'],
            ['self-closing', 'Self-Closing', '/\bself[ -]?closing\b/iu'],
            ['dual-outlet', 'Dual Outlet', '/\bdual[ -]?outlet\b/iu'],
        ];
        foreach ($rules as [$icon, $label, $pattern]) {
            if (preg_match($pattern, $description) === 1) {
                $add($icon, $label);
            }
        }

        if (count($features) < 3) {
            foreach ($specs as $row) {
                $key = (string) ($row['spec_key'] ?? '');
                $value = trim((string) ($row['spec_value'] ?? ''));
                if ($value === '' || preg_match('/material|finish|colou?r|dimension|size|installation|mounting|cartridge|\btype\b/iu', $key) === 1) {
                    continue;
                }
                $add('spec', $value);
            }
        }

        return $features;
    }

    /** @return array<int, array{key: string, label: string}> */
    private static function finishes(array $variants, array $specs, string $description): array
    {
        $finishes = [];
        $seen = [];
        $add = static function (string $value, bool $knownOnly = false) use (&$finishes, &$seen): void {
            $finish = self::finish($value);
            $key = strtolower($finish['label']);
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
            if (preg_match('/finish|colou?r/iu', (string) ($row['spec_key'] ?? '')) === 1) {
                $add((string) ($row['spec_value'] ?? ''));
            }
        }

        if (preg_match_all('/\b(?:finish|colou?r)\s*[:\-]\s*([^,;.\r\n]+)/iu', $description, $matches)) {
            foreach ($matches[1] as $value) {
                $parts = preg_split('/\s+with\s+/iu', (string) $value) ?: [(string) $value];
                foreach ($parts as $index => $part) {
                    $add((string) $part, $index > 0);
                }
            }
        }
        /* One ordered alternation consumes "Rose Gold" as a whole. Separate
           searches would incorrectly add both Rose Gold and Gold. */
        $phrases = array_keys(self::FINISHES);
        usort($phrases, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));
        $pattern = '/\b(' . implode('|', array_map(
            static fn (string $phrase): string => preg_quote($phrase, '/'),
            $phrases,
        )) . ')\s+(?:colou?r|finish)\b/iu';
        if (preg_match_all($pattern, $description, $matches)) {
            foreach ($matches[1] as $phrase) {
                $add((string) $phrase);
            }
        }

        $labels = array_column($finishes, 'label');
        if (in_array('Stainless Steel', $labels, true) && in_array('Steel', $labels, true)) {
            $finishes = array_values(array_filter(
                $finishes,
                static fn (array $finish): bool => $finish['label'] !== 'Steel',
            ));
        }

        return $finishes;
    }

    /** @return array{key: string, label: string} */
    private static function finish(string $value): array
    {
        $value = trim((string) preg_replace('/\s+/u', ' ', $value));
        $value = trim((string) preg_replace('/\s*\(\s*optional\s*\)\s*$/iu', '', $value));
        $value = trim((string) preg_replace('/^\d+\s*[-–]\s*/u', '', $value), " \t\n\r\0\x0B:-");
        if ($value === '' || strlen($value) > 72) {
            return ['key' => 'neutral', 'label' => ''];
        }

        foreach (self::FINISHES as $phrase => [$swatch, $label]) {
            if (preg_match('/\b' . preg_quote($phrase, '/') . '\b/iu', $value) === 1) {
                return ['key' => $swatch, 'label' => $label];
            }
        }

        /* Corrupted legacy bytes are not a finish name. Supported catalogue
           locales all retain at least one ASCII letter in these labels. */
        if (!preg_match('/[a-z]/i', $value)) {
            return ['key' => 'neutral', 'label' => ''];
        }

        return ['key' => 'neutral', 'label' => $value];
    }

    private static function material(array $specs, string $description): string
    {
        $structured = self::specValue($specs, '/\bmaterials?\b/iu');
        if ($structured !== '') {
            return self::canonicalMaterial($structured);
        }

        /* Steel screws, braids, fittings and hoses do not identify body material. */
        if (preg_match('/\bstainless\s+steel(?:\s*(?:aisi\s*|#\s*)?304)?(?!\s*(?:screws?|fittings?|hoses?|braids?)\b)/iu', $description, $match)) {
            return self::canonicalMaterial((string) $match[0]);
        }

        /* "Brass body" outranks a later "Valve Core Material: Ceramic". */
        if (preg_match('/\b(vitreous\s+china|stainless\s+steel|ceramic|brass|acrylic|abs|pp|uf)\s+body\b/iu', $description, $match)) {
            return self::canonicalMaterial((string) $match[1]);
        }

        $known = '(vitreous\s+china|stainless\s+steel(?:\s*(?:aisi\s*|#\s*)?304)?|ceramic|brass|acrylic|abs|pp|uf)';
        if (preg_match('/\bmaterials?\s*[:\-]?\s*' . $known . '/iu', $description, $match)) {
            return self::canonicalMaterial((string) $match[1]);
        }
        if (preg_match('/\b(vitreous\s+china|acrylic)\b/iu', $description, $match)) {
            return self::canonicalMaterial((string) $match[1]);
        }
        if (preg_match('/\bbrass\b(?=[^.]{0,30}\b(?:body|construction|material|chrome|plated|faucet)\b)/iu', $description)) {
            return 'Brass';
        }
        if (preg_match('/\bceramic\b(?!\s+cartridge)/iu', $description)) {
            return 'Ceramic';
        }
        if (preg_match('/\b(abs|pp|uf)\b/iu', $description, $match)) {
            return strtoupper((string) $match[1]);
        }

        return '';
    }

    private static function canonicalMaterial(string $value): string
    {
        if (preg_match('/vitreous\s+china/iu', $value)) return 'Vitreous China';
        if (preg_match('/stainless\s+steel/iu', $value)) return preg_match('/304/', $value) ? 'Stainless Steel 304' : 'Stainless Steel';
        if (preg_match('/\bceramic\b/iu', $value)) return 'Ceramic';
        if (preg_match('/\bbrass\b/iu', $value)) return 'Brass';
        if (preg_match('/\bacrylic\b/iu', $value)) return 'Acrylic';
        if (preg_match('/\b(?:abs|pp|uf)\b/iu', $value, $match)) return strtoupper((string) $match[0]);

        return trim($value);
    }

    private static function installation(array $specs, string $description): string
    {
        $structured = self::specValue($specs, '/installation|mounting/iu');
        $source = $structured !== '' ? $structured : $description;
        $rules = [
            ['Wall Hung', '/\bwall[ -]?hung\b/iu'],
            ['Wall Mounted', '/\bwall[ -]?mounted\b/iu'],
            ['Floor Standing', '/\bfloor[ -]?standing\b/iu'],
            ['Floor Mounted', '/\bfloor[ -]?mounted\b/iu'],
            ['Deck Mounted', '/\bdeck[ -]?mounted\b/iu'],
            ['Countertop', '/\b(?:counter[ -]?top|over[ -]?top\s+basin)\b/iu'],
            ['Freestanding', '/\bfree[ -]?standing\b/iu'],
            ['Concealed', '/\bconcealed\b/iu'],
            ['Built-in', '/\bbuilt[ -]?in\b/iu'],
        ];
        foreach ($rules as [$label, $pattern]) {
            if (preg_match($pattern, $source) === 1) {
                return $label;
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

        $structured = self::specValue($specs, '/dimension|\bsize\b/iu');
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

    private static function specValue(array $specs, string $keyPattern): string
    {
        foreach ($specs as $row) {
            if (preg_match($keyPattern, (string) ($row['spec_key'] ?? '')) === 1) {
                $value = trim((string) ($row['spec_value'] ?? ''));
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
