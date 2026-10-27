<?php

declare(strict_types=1);

namespace Modules\Products\Services;

/**
 * Single source of truth for the SVG stroke icons used on product cards,
 * quick-spec rows, feature presets, and the upcoming Admin Dashboard icon picker.
 */
final class ProductFeatureIconCatalog
{
    /**
     * @var array<string, array{paths: string, labels: array{en: string, tr: string, cs: string}}>
     */
    public const ICONS = [
        'dimensions' => [
            'paths' => '<path d="M4 5h16v14H4z"/><path d="M8 5v4M12 5v3M16 5v4M8 19v-4M12 19v-3M16 19v-4"/>',
            'labels' => ['en' => 'Dimensions', 'tr' => 'Ölçüler', 'cs' => 'Rozměry'],
        ],
        'material' => [
            'paths' => '<path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z"/><path d="m4 7.5 8 4.5 8-4.5M12 12v9"/>',
            'labels' => ['en' => 'Material', 'tr' => 'Malzeme', 'cs' => 'Materiál'],
        ],
        'installation' => [
            'paths' => '<path d="M5 4h14v16H5z"/><path d="M9 8h6M9 12h6M9 16h3"/>',
            'labels' => ['en' => 'Installation', 'tr' => 'Montaj Tipi', 'cs' => 'Instalace'],
        ],
        'finish' => [
            'paths' => '<circle cx="12" cy="12" r="8"/><path d="M12 4a8 8 0 0 1 0 16M6.5 9.5l11 5"/>',
            'labels' => ['en' => 'Surface Finish', 'tr' => 'Yüzey Kaplaması', 'cs' => 'Povrchová úprava'],
        ],
        'cartridge' => [
            'paths' => '<path d="M6 20V9a6 6 0 0 1 12 0v3"/><path d="M12 12v8M4 20h16M15 6h5"/>',
            'labels' => ['en' => 'Valve Cartridge', 'tr' => 'Kartuş Mekanizması', 'cs' => 'Kartuše'],
        ],
        'rimless' => [
            'paths' => '<ellipse cx="12" cy="9" rx="8" ry="4.5"/><path d="M4 9v4c0 3.2 3.2 5.5 8 5.5s8-2.3 8-5.5V9M8 21h8"/>',
            'labels' => ['en' => 'Rimless Bowl', 'tr' => 'Kanalsız Yıkama', 'cs' => 'Bezokrajové splachování'],
        ],
        'soft-close' => [
            'paths' => '<path d="M4 7h16M4 12h16M4 17h16"/><path d="m8 4 8 16"/>',
            'labels' => ['en' => 'Soft-Close', 'tr' => 'Yavaş Kapanır', 'cs' => 'Pomalé sklápění'],
        ],
        'water-saving' => [
            'paths' => '<path d="M12 3S6.5 9.2 6.5 13.4a5.5 5.5 0 0 0 11 0C17.5 9.2 12 3 12 3Z"/><path d="m9.5 13.5 1.6 1.6 3.4-3.6"/>',
            'labels' => ['en' => 'Water Efficiency', 'tr' => 'Su Tasarrufu', 'cs' => 'Úspora vody'],
        ],
        'easy-clean' => [
            'paths' => '<path d="m12 3 1.5 5.5L19 10l-5.5 1.5L12 17l-1.5-5.5L5 10l5.5-1.5L12 3Z"/><path d="m18 16 .7 2.3L21 19l-2.3.7L18 22l-.7-2.3L15 19l2.3-.7L18 16Z"/>',
            'labels' => ['en' => 'Easy Clean', 'tr' => 'Kolay Temizlenir', 'cs' => 'Snadné čištění'],
        ],
        'scratch-resistant' => [
            'paths' => '<path d="M12 3 19 6.5v5.2c0 4.5-3 7.8-7 9.3-4-1.5-7-4.8-7-9.3V6.5L12 3Z"/><path d="m9 14 6-6"/>',
            'labels' => ['en' => 'Surface Protection', 'tr' => 'Çizilme Direnci', 'cs' => 'Ochrana povrchu'],
        ],
        'single-lever' => [
            'paths' => '<path d="M7 20v-7a5 5 0 0 1 10 0v7"/><path d="M5 20h14M12 8V4m0 0h5"/>',
            'labels' => ['en' => 'Lever Control', 'tr' => 'Kumanda Tipi', 'cs' => 'Ovládání'],
        ],
        'thermostatic' => [
            'paths' => '<path d="M12 14a3 3 0 1 0 0 6 3 3 0 0 0 0-6Z"/><path d="M10.5 14.5V6a1.5 1.5 0 0 1 3 0v8.5M16 7h3M16 10h2"/>',
            'labels' => ['en' => 'Thermostatic', 'tr' => 'Termostatik', 'cs' => 'Termostat'],
        ],
        'shower' => [
            'paths' => '<path d="M12 3v5M6 12a6 6 0 0 1 12 0M5 12h14"/><path d="M8 16v1M12 16v1M16 16v1M8 20v1M12 20v1M16 20v1"/>',
            'labels' => ['en' => 'Spray System', 'tr' => 'Akış Sistemi', 'cs' => 'Proud vody'],
        ],
        'high-flow' => [
            'paths' => '<path d="M4 8h12M4 12h16M4 16h12"/><path d="m14 5 3 3-3 3M14 13l3 3-3 3"/>',
            'labels' => ['en' => 'Flow Performance', 'tr' => 'Akış Performansı', 'cs' => 'Průtok'],
        ],
        'anti-odor' => [
            'paths' => '<path d="M12 3 20 6v5c0 4.8-3.3 8.3-8 10-4.7-1.7-8-5.2-8-10V6l8-3Z"/><path d="m8.5 12 2.2 2.2 4.8-5"/>',
            'labels' => ['en' => 'Odor & Trap Seal', 'tr' => 'Koku Kilidi', 'cs' => 'Zápachová uzávěra'],
        ],
        'connection' => [
            'paths' => '<circle cx="8" cy="12" r="3.5"/><circle cx="16" cy="12" r="3.5"/><path d="M11.5 12h1M4.5 12H2M22 12h-2.5"/>',
            'labels' => ['en' => 'Connection', 'tr' => 'Bağlantı', 'cs' => 'Připojení'],
        ],
        'drainage' => [
            'paths' => '<path d="M4 7h16M6 7l1.5 6h9L18 7"/><path d="M12 13v7m-3-2 3 2 3-2"/>',
            'labels' => ['en' => 'Drainage & Trap', 'tr' => 'Tahliye Sistemi', 'cs' => 'Odpad a sifon'],
        ],
        'sensor' => [
            'paths' => '<circle cx="12" cy="12" r="2.2"/><path d="M8.5 8.5a5 5 0 0 0 0 7M15.5 8.5a5 5 0 0 1 0 7M6 6a8.5 8.5 0 0 0 0 12M18 6a8.5 8.5 0 0 1 0 12"/>',
            'labels' => ['en' => 'Infrared Sensor', 'tr' => 'Kızılötesi Sensör', 'cs' => 'Infračervený senzor'],
        ],
        'load-capacity' => [
            'paths' => '<path d="M6 19h12l-1.5-9h-9L6 19Z"/><path d="M9.5 10a2.5 2.5 0 0 1 5 0M10 14.5h4"/>',
            'labels' => ['en' => 'Load Rating', 'tr' => 'Taşıma Kapasitesi', 'cs' => 'Nosnost'],
        ],
        'hygiene' => [
            'paths' => '<path d="M12 4v16M4 12h16"/><circle cx="12" cy="12" r="8.5"/>',
            'labels' => ['en' => 'Hygienic Grade', 'tr' => 'Hijyen Sınıfı', 'cs' => 'Hygienická třída'],
        ],
        'certification' => [
            'paths' => '<circle cx="12" cy="9.5" r="5.5"/><path d="m9.5 14.2-1.3 6.3L12 18.5l3.8 2-1.3-6.3"/><path d="m10 9.5 1.4 1.4 2.8-2.8"/>',
            'labels' => ['en' => 'Standard', 'tr' => 'Standart', 'cs' => 'Norma'],
        ],
        'warranty' => [
            'paths' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
            'labels' => ['en' => 'Durability', 'tr' => 'Dayanıklılık', 'cs' => 'Životnost'],
        ],
        'pressure' => [
            'paths' => '<path d="M5.5 17.5a8 8 0 1 1 13 0"/><path d="m12 12 3.5-3.5"/><circle cx="12" cy="12" r="1.3"/>',
            'labels' => ['en' => 'Operating Pressure', 'tr' => 'Çalışma Basıncı', 'cs' => 'Provozní tlak'],
        ],
        'spec' => [
            'paths' => '<circle cx="12" cy="12" r="8.5"/><path d="m8.5 12 2.2 2.2 4.8-5"/>',
            'labels' => ['en' => 'Specification', 'tr' => 'Teknik Özellik', 'cs' => 'Parametr'],
        ],
    ];

    /**
     * Friendly aliases mapped to canonical ICONS keys.
     *
     * @var array<string, string>
     */
    private const ALIASES = [
        'mounting' => 'installation',
        'water' => 'water-saving',
        'spray' => 'shower',
        'drain' => 'drainage',
        'standard' => 'certification',
        'thermostat' => 'thermostatic',
        'capacity' => 'load-capacity',
        'accessibility' => 'hygiene',
        'spark' => 'easy-clean',
        'light' => 'sensor',
        'power' => 'sensor',
        'shape' => 'spec',
    ];

    public static function has(string $key): bool
    {
        $normalized = strtolower(trim($key));
        if ($normalized === '') {
            return false;
        }

        return isset(self::ICONS[$normalized]) || isset(self::ALIASES[$normalized]);
    }

    public static function normalize(string $key): string
    {
        $normalized = strtolower(trim($key));
        if (isset(self::ICONS[$normalized])) {
            return $normalized;
        }
        if (isset(self::ALIASES[$normalized])) {
            return self::ALIASES[$normalized];
        }

        return 'spec';
    }

    public static function paths(string $key): string
    {
        $normalized = self::normalize($key);

        return self::ICONS[$normalized]['paths'];
    }

    public static function svg(string $key): string
    {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.45" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
            . self::paths($key)
            . '</svg>';
    }

    /**
     * Full icon list for Admin Dashboard icon pickers, localized to $locale.
     *
     * @return array<int, array{key: string, label: string, svg: string}>
     */
    public static function all(string $locale = 'en'): array
    {
        $out = [];
        foreach (self::ICONS as $key => $meta) {
            $out[] = [
                'key' => $key,
                'label' => $meta['labels'][$locale] ?? $meta['labels']['en'],
                'svg' => self::svg($key),
            ];
        }

        return $out;
    }

    /**
     * Alias of all() for Dashboard form builders.
     *
     * @return array<int, array{key: string, label: string, svg: string}>
     */
    public static function options(string $locale = 'en'): array
    {
        return self::all($locale);
    }
}
