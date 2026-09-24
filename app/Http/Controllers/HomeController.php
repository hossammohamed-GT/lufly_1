<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\SEOService;
use Core\Http\RedirectResponse;
use Core\Http\Response;
use Core\Localization\Translator;

class HomeController extends Controller
{
    public function __construct(
        private readonly Translator $translator,
        private readonly SEOService $seo,
    ) {
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
        $categories = $connection->select(
            "SELECT c.id, c.slug, c.image,
                    COALESCE(ct.name, ctf.name) AS name,
                    COALESCE(ct.description, ctf.description) AS description
             FROM categories c
             LEFT JOIN category_translations ct ON ct.category_id = c.id AND ct.locale = ?
             LEFT JOIN category_translations ctf ON ctf.category_id = c.id AND ctf.locale = ?
             WHERE c.deleted_at IS NULL AND c.status = 'active'
             ORDER BY c.id ASC",
            [$locale, $fallback]
        );

        $shotRows = $connection->select(
            "SELECT p.category_id, m.path
               FROM products p
               JOIN product_media pm ON pm.product_id = p.id
               JOIN media m ON m.id = pm.media_id
              WHERE p.status = 'active' AND p.deleted_at IS NULL
                AND m.deleted_at IS NULL AND m.status = 'active'
                AND m.mime_type LIKE 'image/%'
                AND pm.type IN ('main', 'gallery')
              ORDER BY p.category_id ASC, (pm.type = 'main') DESC,
                       pm.is_primary DESC, pm.sort_order ASC, pm.id ASC",
        );

        $shotsByCategory = [];
        foreach ($shotRows as $row) {
            $categoryId = (int) $row['category_id'];
            $path = trim((string) $row['path']);
            if ($path !== '' && !isset($shotsByCategory[$categoryId][$path])) {
                $shotsByCategory[$categoryId][$path] = true;
            }
        }

        $shotsPerTile = 3;
        foreach ($categories as &$category) {
            $shots = array_slice(array_keys($shotsByCategory[(int) $category['id']] ?? []), 0, 8);
            shuffle($shots);
            $shots = array_slice($shots, 0, $shotsPerTile);
            if ($shots === []) {
                $own = trim((string) ($category['image'] ?? ''));
                $shots = $own !== '' ? [$own] : [];
            }
            $category['shots'] = $shots;
        }
        unset($category);

        $pool = $connection->select(
            "SELECT id, category_id, is_featured
               FROM products
              WHERE status = 'active' AND deleted_at IS NULL",
        );

        $byCategory = [];
        foreach ($pool as $row) {
            $byCategory[(int) $row['category_id']][] = $row;
        }

        $ids = [];
        foreach ($byCategory as $list) {
            $featuredOnly = array_values(array_filter($list, static fn ($r) => (int) $r['is_featured'] === 1));
            $bucket = $featuredOnly !== [] ? $featuredOnly : $list;
            $ids[] = (int) $bucket[array_rand($bucket)]['id'];
        }
        shuffle($ids);                 $ids = array_slice($ids, 0, 4); $rawFeatured = [];
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

        $featuredProducts = array_map(function ($p) use ($locale) {
            return $p->translate($locale);
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
        $this->seo->setImage(asset('/images/lifestyle/heroc-1.webp'));
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
            'featuredProducts' => $featuredProducts,
            'supportedLocales' => $this->translator->supported(),
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
