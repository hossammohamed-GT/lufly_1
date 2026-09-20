# Architecture

LUFLY runs on a **custom, dependency-free PHP framework** living in `core/`.
No Composer, no vendor packages — everything (routing, container, views, DB, CLI) is hand-rolled
in this repository. This is a deliberate choice so the site can be deployed on plain
shared hosting with FTP upload only.

## Request lifecycle

```
HTTP request
  └─► public/index.php            (front controller)
        └─► bootstrap.php         (registers the autoloader, builds the Application container)
              └─► Core\Http\Kernel
                    ├─► loads routes: routes/web.php · routes/admin.php · routes/api.php
                    │                + modules/<Module>/Routes/routes.php
                    ├─► middleware pipeline (web / api / auth)
                    ├─► controller
                    ├─► View::render → layout + partials + components
                    └─► Response::send
```

- **`.htaccess`** (repo root) rewrites everything that is not a real file to `public/index.php` —
  so the project works both with docroot = project root (XAMPP `htdocs/lufly_1/`) and docroot = `public/`.
- **`server.php`** is the router script for `php -S` (dev).
- **`cli`** is the console entry point (`php cli migrate`, …).

## Autoloading

`bootstrap.php` registers a tiny PSR-4-style autoloader:

| Prefix | Directory |
|---|---|
| `Core\` | `core/` |
| `App\` | `app/` |
| `Modules\` | `modules/` |

Adding a PHP file in the right folder with the matching namespace = autoloaded. Nothing else to do.

## Modules

Each module in `modules/<Module>/` is self-contained:

```
modules/Products/
├── Controllers/      ProductController (web + admin), Api/ProductApiController
├── Models/           Product, ProductTranslation, ProductMedia… (Core\Database\Model)
├── Repositories/     Query logic (DB access lives HERE, not in controllers)
├── Services/         Business operations used by >1 controller
├── Requests/         Form validation (Core\Validation)
├── Routes/routes.php Public + admin + api routes for this module
└── Views/            index.php (catalog), show.php (detail), Admin/*.php
```

Enabled modules are listed in `config/modules.php`:

`Authentication, Users, Permissions, Languages, Products, Media, Settings, SEO, Notifications, Announcements`

## The View engine (important conventions)

Views are plain PHP templates. Two ways to include another view:

```php
// 1. Full dot-path resolution — "components.navbar" → resources/views/components/navbar.php
$view->renderFile($view->resolvePath('components.navbar'), $data);

// 2. Short helper — components ONLY (same directory, bare name):
//    `$view->component('flag')` → resources/views/components/flag.php
echo $view->component('flag', ['code' => $locale]);
```

> ⚠️ When renaming/deleting a component, search for **both** conventions
> (`components.<name>` **and** `->component('<name>')`). Deleting `flag.php`
> once broke the navbar because only the first pattern was checked.

Assets are attached from inside a view partial:

```php
$view->pushStyle('frontend/home/finishes/finishes.css');
$view->pushScript('frontend/home/finishes/finishes.js');
```

The layout (`resources/views/layouts/frontend.php` / `admin.php`) prints the pushed
styles in `<head>` and scripts before `</body>`. **Every CSS/JS file under `frontend/`
is referenced from a view** — there is no build step and no unused-asset folder.

Home page = 8 sections rendered in order by `resources/views/home/index.php`:
`hero-cinema · trust-bar · finishes · categories · inspiration · rituals · masterpieces · corporate`

## Database access

- `Core\Database\DatabaseManager` → `Connection` (PDO, driver chosen by `DB_CONNECTION`: `mysql` | `sqlite`).
- `Core\Database\Model` — active-record-ish models (`Module\Models\*`), hydrated `fromRow()`.
- `Core\Database\QueryBuilder` — dialect-agnostic queries; produced SQL works on **both** MySQL and SQLite.
- Schema source of truth: `database/schema/*.php` (`Blueprint`), mirrored by `database/migrations/*`.
  Index names are generated as `table_columns_unique/index` and automatically shortened
  to respect MySQL's 64-identifier limit.

## Middleware groups

| Group | Purpose |
|---|---|
| `web` | Cookies, session, CSRF, locale detection for public/admin pages |
| `api` | JSON API (`/api/…`) — `ApiResponse` envelope |
| `auth` | Requires a logged-in user (admin routes) |

## Localization in the router

`$router->localized('GET', 'contact', …)` registers **one route per locale** with translated slugs
(`/en/contact`, `/tr/iletisim`, `/cs/kontakt`). Slugs come from `resources/lang/<locale>/routes.php`.
See [Localization.md](Localization.md).

## CLI

`cli` → `Core\Console\Kernel` → commands in `core/Console/Commands/`
(`migrate, seed, serve, db:export-mysql, backup:db, schema:dump, erd, key:generate, docs:api, list, rollback`).
