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

        $rawFeatured = \Modules\Products\Models\Product::query()
            ->where('status', 'active')
            ->limit(6)
            ->get();

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

        return $this->view('home.index', [
            'title' => $pageTitle,
            'locale' => $locale,
            'categories' => $categories,
            'featuredProducts' => $featuredProducts,
            'supportedLocales' => $this->translator->supported(),
            'seo' => $this->seo,
        ]);
    }
}
