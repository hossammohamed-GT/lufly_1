<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\CacheService;
use App\Services\CategoryService;
use App\Services\SEOService;
use Core\Http\RedirectResponse;
use Core\Http\Response;
use Core\Localization\Translator;
use Modules\Products\Services\ProductVisibility;

class HomeController extends Controller
{
    /**
     * How long the catalogue-wide lookups below are kept.
     *
     * They are the only part of the home page whose cost grows with the number
     * of products, and they only change when somebody edits the catalogue, so
     * five minutes of staleness here is invisible next to a render that is
     * deliberately different on every visit.
     */
    private const CACHE_TTL = 300;

    public function __construct(
        private readonly Translator $translator,
        private readonly SEOService $seo,
        private readonly CacheService $cache,
        private readonly CategoryService $categoryService,
    ) {
    }

    /**
     * Fetch through the cache. CacheService::get() is a single file read, so
     * this stays cheaper than re-running the query even for the current, small
     * catalogue - and it stops mattering entirely once the catalogue grows.
     *
     * @param callable(): array<int, array<string, mixed>> $callback
     * @return array<int, array<string, mixed>>
     */
    private function remember(string $key, callable $callback): array
    {
        $cached = $this->cache->get($key);

        if (is_array($cached)) {
            return $cached;
        }

        $value = $callback();
        $this->cache->set($key, $value, self::CACHE_TTL);

        return $value;
    }

    public function root(): RedirectResponse
    {
        return new RedirectResponse(route('home'));
    }

    public function index(): Response
    {
        $locale = $this->translator->getLocale();
        $connection = \Modules\Products\Models\Product::query()->connection();

        $fallback = (string) config('localization.fallback', 'en');

        $categoryCounts = $this->categoryService->productCounts();
        $categories = $this->categoryService->nonEmptyTree($locale, $fallback, $categoryCounts);

        /* Rotating showcase: every visit picks ONE random product per
           category, then keeps 4 for the grid - so each reload shows a
           different mix that always spans distinct categories. Curated
           (is_featured) products win within their category when any exist. */
        /* Media readiness is intentionally uncached: uploading the missing
           photo/drawing restores a product immediately, while removing either
           one removes it from the showcase on the very next request. */
        $colorFamilies = app(\Modules\Products\Services\ProductColorFamilyService::class);
        $pool = $colorFamilies->collapseRows($connection->select(
            "SELECT p.id, p.category_id, p.is_featured, p.sort_order
               FROM products p
              WHERE p.status = 'active' AND p.deleted_at IS NULL
                AND " . ProductVisibility::mediaReadySql('p') . "
              ORDER BY p.is_featured DESC, p.sort_order ASC, p.id DESC",
        ));

        /* Featured products take priority slots FIRST in strict Admin priority
           order (sort_order ASC, id ASC). Remaining slots up to 4 rotate across
           distinct non-featured categories so the Home showcase stays vibrant. */
        $featuredRows = [];
        $regularByCategory = [];
        foreach ($pool as $row) {
            if ((int) ($row['is_featured'] ?? 0) === 1) {
                $featuredRows[] = $row;
            } else {
                $regularByCategory[(int) $row['category_id']][] = $row;
            }
        }
        usort(
            $featuredRows,
            static fn (array $a, array $b): int => ((int) ($a['sort_order'] ?? PHP_INT_MAX) <=> (int) ($b['sort_order'] ?? PHP_INT_MAX))
                ?: ((int) ($a['id'] ?? 0) <=> (int) ($b['id'] ?? 0)),
        );

        $pinnedIds = array_map(static fn (array $r): int => (int) $r['id'], $featuredRows);
        $featuredCategories = array_flip(array_map(static fn (array $r): int => (int) $r['category_id'], $featuredRows));

        $rotatingIds = [];
        $fallbackRotatingIds = [];
        foreach ($regularByCategory as $catId => $list) {
            $chosen = (int) $list[array_rand($list)]['id'];
            if (isset($featuredCategories[$catId])) {
                $fallbackRotatingIds[] = $chosen;
            } else {
                $rotatingIds[] = $chosen;
            }
        }
        shuffle($rotatingIds);
        shuffle($fallbackRotatingIds);

        $ids = array_slice(array_values(array_unique(array_merge($pinnedIds, $rotatingIds, $fallbackRotatingIds))), 0, 4);

        $savedIds = [];
        if (feature('favorites', true) && class_exists(\Modules\Favorites\Services\FavoriteService::class)) {
            $savedIds = array_flip(app(\Modules\Favorites\Services\FavoriteService::class)->productIds());
        }
        $boxIds = [];
        if (feature('box', true) && class_exists(\Modules\Box\Services\BoxService::class)) {
            $boxIds = array_flip(app(\Modules\Box\Services\BoxService::class)->productIds());
        }

        $rawFeatured = [];
        if ($ids !== []) {
            $byId = [];
            foreach (\Modules\Products\Models\Product::query()->whereIn('id', $ids)->get() as $model) {
                $byId[(int) $model->getKey()] = $model;
            }
            foreach ($ids as $id) {
                if (isset($byId[$id])) {
                    $rawFeatured[] = $byId[$id];
                }
            }
        }

        \Modules\Products\Models\Product::eagerLoad($rawFeatured);

        $featuredProducts = array_map(function ($p) use ($locale, $colorFamilies, $savedIds, $boxIds) {
            return $colorFamilies->attach($p->translate($locale), $locale, $savedIds, $boxIds);
        }, $rawFeatured);

        $seoTitles = [
            'en' => 'LUFLY | European Architectural Sanitary Ware Manufacturer & Global Export',
            'tr' => 'LUFLY | Mimari Vitrifiye ve Sıhhi Tesisat Üreticisi & İhracat',
            'cs' => 'LUFLY | Architektonická sanitární technika a evropský export',
        ];

        $seoDescriptions = [
            'en' => 'LUFLY manufactures European certified architectural sanitary ware, rimless toilets, luxury PVD brassware, and hydrotherapy systems for premier residential and hospitality projects worldwide.',
            'tr' => 'LUFLY, lüks konut ve otel projeleri için Avrupa standartlarında kanalsız asma klozetler, PVD kaplama bataryalar ve hidrodinamik duş sistemleri üretir.',
            'cs' => 'LUFLY vyrábí evropsky certifikovanou sanitární keramiku, bezokrajová WC, prémiové PVD baterie a hydroterapeutické sprchové systémy pro luxusní projekty.',
        ];

        $pageTitle = $seoTitles[$locale] ?? $seoTitles['en'];
        $pageDesc = $seoDescriptions[$locale] ?? $seoDescriptions['en'];

        $this->seo->setTitle($pageTitle);
        $this->seo->setDescription($pageDesc);
        $this->seo->setCanonical(route('home'));
        $this->seo->setImage(asset('/images/hero/hero-bathroom.webp'));
        $this->seo->setAlternatesFor('home');
        $this->seo->addWebPageSchema([
            'name' => $pageTitle,
            'description' => $pageDesc,
            'url' => route('home'),
            'inLanguage' => $locale,
        ]);

        return $this->view('home.index', [
            'title' => $pageTitle,
            'locale' => $locale,
            'categories' => $categories,
            'categoryCounts' => $categoryCounts,
            'featuredProducts' => $featuredProducts,
            'savedIds' => $savedIds,
            'boxIds' => $boxIds,
            'supportedLocales' => $this->translator->supported(),
            'seo' => $this->seo,
        ]);
    }

    public function centralVacuum(): Response
    {
        $locale = $this->translator->getLocale();

        $pageTitle = trans('central_vacuum.seo_title');
        $pageDesc = trans('central_vacuum.seo_desc');

        $this->seo->setTitle($pageTitle);
        $this->seo->setDescription($pageDesc);
        $this->seo->setCanonical(route('central-vacuum'));
        $this->seo->setImage(url('/og-image?title=' . rawurlencode($pageTitle) . '&subtitle=' . rawurlencode($pageDesc)));
        $this->seo->setAlternatesFor('central-vacuum');
        $this->seo->addWebPageSchema([
            'name' => $pageTitle,
            'description' => $pageDesc,
            'url' => route('central-vacuum'),
            'inLanguage' => $locale,
        ]);
        $this->seo->addBreadcrumb([
            ['name' => 'LUFLY', 'url' => route('home')],
            ['name' => trans('central_vacuum.nav_label'), 'url' => route('central-vacuum')],
        ]);

        return $this->view('central-vacuum.index', [
            'title' => $pageTitle,
            'locale' => $locale,
            'seo' => $this->seo,
        ]);
    }

    public function contact(): Response
    {
        $locale = $this->translator->getLocale();

        $seoTitles = [
            'en' => 'Contact LUFLY | WhatsApp, Email and Factory Direct Line',
            'tr' => 'LUFLY İletişim | WhatsApp, E-posta ve Fabrika Direkt Hattı',
            'cs' => 'Kontakt LUFLY | WhatsApp, e-mail a přímá linka továrny',
        ];

        $seoDescriptions = [
            'en' => 'Reach the LUFLY architectural team directly: WhatsApp +90 850 3040 817, email info@lufly.tr, catalog and BIM requests, and our Gaziantep manufacturing and export hub in Turkey.',
            'tr' => 'LUFLY mimari ekibine doğrudan ulaşın: WhatsApp +90 850 3040 817, e-posta info@lufly.tr, katalog ve BIM talepleri ve Gaziantep üretim ile ihracat üssümüz.',
            'cs' => 'Oslovte architektonický tým LUFLY přímo: WhatsApp +90 850 3040 817, e-mail info@lufly.tr, žádosti o katalog a BIM a naše výrobní a exportní centrum v Gaziantepu.',
        ];

        $pageTitle = $seoTitles[$locale] ?? $seoTitles['en'];
        $pageDesc = $seoDescriptions[$locale] ?? $seoDescriptions['en'];

        $this->seo->setTitle($pageTitle);
        $this->seo->setDescription($pageDesc);
        $this->seo->setCanonical(route('contact'));
        $this->seo->setAlternatesFor('contact');
        $this->seo->addWebPageSchema([
            'type' => 'ContactPage',
            'name' => $pageTitle,
            'description' => $pageDesc,
            'url' => route('contact'),
            'inLanguage' => $locale,
        ]);

        /* FAQ structured data mirrors exactly what the page renders, from the
           same translation keys, so schema and content can never drift. */
        $faqs = [];
        for ($i = 1; $i <= 4; $i++) {
            $q = trans('contact.faq_' . $i . '_q');
            $a = trans('contact.faq_' . $i . '_a');
            if ($q !== 'contact.faq_' . $i . '_q' && $a !== 'contact.faq_' . $i . '_a') {
                $faqs[] = ['q' => $q, 'a' => $a];
            }
        }
        if ($faqs !== []) {
            $this->seo->addFaqSchema($faqs);
        }

        $this->seo->addBreadcrumb([
            ['name' => 'LUFLY', 'url' => route('home')],
            ['name' => $pageTitle, 'url' => route('contact')],
        ]);

        return $this->view('contact.index', [
            'title' => $pageTitle,
            'locale' => $locale,
            'seo' => $this->seo,
        ]);
    }
}
