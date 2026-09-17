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

        $categories = $connection->select(
            "SELECT c.id, c.slug, c.image, ct.name, ct.description FROM categories c
             LEFT JOIN category_translations ct ON ct.category_id = c.id AND ct.locale = ?
             WHERE c.deleted_at IS NULL AND c.status = 'active'
             ORDER BY c.id ASC",
            [$locale]
        );

        $featuredProducts = \Modules\Products\Models\Product::query()
            ->where('status', 'active')
            ->limit(6)
            ->get();

        return $this->view('home', [
            'title' => trans('home.hero_title'),
            'locale' => $locale,
            'categories' => $categories,
            'featuredProducts' => $featuredProducts,
            'supportedLocales' => $this->translator->supported(),
        ]);
    }
}
