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

Each component renders whatever it can from the data it is handed **and returns
early when a list is empty** — so a section that needs controller data has to
receive it at the call site:

```php
<?= $component('categories', ['categories' => $categories ?? []]) ?>
```

Without the hand-off the band disappears silently (that is how the category
mosaic went missing); with `APP_DEBUG=true` the component leaves a
`<!-- home.categories: no categories passed -->` comment instead.

## The home hero (one screen, always)

`frontend/home/hero-cinema/hero-cinema.js` sizes the hero so the headline fits the
first screen at every breakpoint. When touching it, keep these invariants:

- **Measure in document space.** `rect.top + scrollY`, never the raw client rect:
  a client rect goes negative while scrolling and re-fitting then grows the hero
  by the scroll offset (each resize event = one more jump).
- **Never trust `window.innerHeight`.** It grows when mobile browsers hide their
  toolbars, so a mid-scroll resize inflates the hero (and the `cover` artwork zoom
  with it). Read the `100svh` probe instead; the fallback remembers the smallest
  `innerHeight` seen so far.
- **Clamp the result**: to the space the first screen actually leaves
  (`viewport − announcements − nav − gap`), to the height the copy needs, to the
  landscape ceiling, and - on portrait phones - to a portrait artwork cap
  (`1.35 × width`), so a tall crop is never blown up into a close-up. Blur
  (WebKit `filter`) also needs a taller minimum than a flat colour: that is the
  `max(1.5 × width, …)` term.
- **Debounce with `requestAnimationFrame`** and re-fit on `resize`,
  `orientationchange`, `visualViewport`, `fonts.ready`, breakpoint/orientation
  media queries, and `<html>` attribute changes (the announcement bar resizes
  `--luann-h`; the theme flips the artwork).

The hero clips its overflow (`.lfc { overflow: hidden }`), so whatever does not fit
the panel simply disappears - which is why the short-screen blocks exist: at
`max-height: 700` the type and paddings shrink, at `640` the paragraph goes, and at
`560` the kicker, the tag and the rule go as well while the brand box and the CTAs
shrink (a 568x320 landscape phone gets ~176px of copy inside a 224px panel). The
landscape-phone fallback height subtracts both `--luann-h` and `--mnav-row1`, so it
fits the same space the script measures.

Artwork: `heroc-<n>{,-m,-p}.webp` (dark) and `heroc-<n>-light{,-m,-p}.jpg` (light),
where `""` is desktop landscape, `-m` is a ≤760px landscape phone and `-p` is a
≤760px portrait phone crop. All 30 files must exist - the URL is chosen at runtime
from the viewport and the theme, so a missing file is an invisible broken image.
`hero-cinema.php` preloads the variant that matches the current orientation via
`media` queries.

Verify changes without a browser:

```bash
node tools/frontend_audit/hero_fit_test.mjs              # 9 simulated devices, exit 1 on regression
node tools/frontend_audit/hero_fit_test.mjs --legacy-viewport
node tools/frontend_audit/hero_report.mjs                # storage/reports/hero-fit.html
node tools/frontend_audit/css_audit.mjs                  # cascade/layout sweep
```

Other home-page invariants worth keeping: the announcement bar exposes its height
as `--luann-h` and every sticky/oversized element subtracts it; the mobile
rail/drawer use `100dvh` (not `100vh`, which ends under the browser toolbar);
`body.ready` must not re-enable horizontal scrolling (`overflow-y: auto` only);
and `content-visibility: auto` sections need a `contain-intrinsic-size` close to
their real height, otherwise the document grows section by section while scrolling.

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
