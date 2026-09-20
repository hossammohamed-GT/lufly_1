# Localization

The site runs **three locales**: `en` (English, default), `tr` (Türkçe), `cs` (Čeština).

## Where language is defined

1. **Config** — `config/localization.php`:
   ```php
   'supported' => ['en' => 'English', 'tr' => 'Türkçe', 'cs' => 'Čeština'],
   'default'  => env('APP_LOCALE', 'en'),
   'fallback' => env('APP_FALLBACK_LOCALE', 'en'),
   ```
2. **Database** — `languages` table (seeded by `LanguageSeeder`). Both lists must agree.
3. **UI strings** — `resources/lang/<locale>/<group>.php`:
   `nav, home, products, contact, common, auth, errors, announcements, routes`.
   Loaded via the translator (`Core\Localization\Translator`) as `trans('home.hero_title')`.

## URLs are translated too (localized routes)

`$router->localized('GET', 'products.index', …)` registers one URL **per locale**, with slugs
coming from `resources/lang/<locale>/routes.php`:

| Route name | en | tr | cs |
|---|---|---|---|
| home | `/en` | `/tr` | `/cs` |
| contact | `/en/contact` | `/tr/iletisim` | `/cs/kontakt` |
| login | `/en/login` | `/tr/giris` (per routes.php) | `/cs/prihlaseni` … |
| products | `/en/products` | `/tr/urunler` … | `/cs/produkty` … |

Add/confirm slugs in `resources/lang/*/routes.php` whenever you add a localized route.

## DB translations (translatable entities)

`config/localization.php → 'translatable'` lists entities backed by `<entity>_translations`
tables: `products`, `categories`, `pages`, `blogs`.
Repositories join the translations table on `locale = current` with fallback to English.

## Adding a new language (checklist)

1. Insert the language into `languages` (admin panel → Languages, or extend `LanguageSeeder` + re-seed).
2. Add it to `config/localization.php → supported`.
3. Copy `resources/lang/en/` → `resources/lang/<new>/` and translate all group files
   (including `routes.php` slugs).
4. Add `<entity>_translations` rows for every product/category (admin → Products, edit per locale),
   plus `seo_meta` rows.
5. Regenerate the MySQL export: `php cli db:export-mysql`.

No code changes are needed beyond the config entry — routes, menus and the language
switcher in the navbar pick the new locale up automatically.

## Navbar flag component

The language switcher renders flags via `$view->component('flag', ['code' => $locale])`
→ `resources/views/components/flag.php`. If you add a locale, extend that component with
the new flag markup.
