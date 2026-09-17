<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Core\Http\RedirectResponse;
use Core\Http\Response;
use Core\Localization\Translator;

class HomeController extends Controller
{
    public function __construct(private readonly Translator $translator)
    {
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

        return $this->view('home.index', [
            'title' => 'LUFLY | Architectural Sanitary Ware Manufacturer & European Export',
            'locale' => $locale,
            'categories' => $categories,
            'featuredProducts' => $featuredProducts,
            'supportedLocales' => $this->translator->supported(),
        ]);
    }
}
