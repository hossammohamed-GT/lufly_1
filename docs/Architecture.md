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
`hero · trust-bar · finishes · categories · inspiration · rituals · masterpieces · corporate`

Each component renders whatever it can from the data it is handed **and returns
early when a list is empty** — so a section that needs controller data has to
receive it at the call site:

```php
<?= $component('categories', ['categories' => $categories ?? []]) ?>
```

Without the hand-off the band disappears silently (that is how the category
mosaic went missing); with `APP_DEBUG=true` the component leaves a
`<!-- home.categories: no categories passed -->` comment instead.

## The home hero ("Crafting Water")

`frontend/home/hero/` is a cinematic full-bleed hero: one photograph per
collection (bathroom, kitchen, shower, accessories, smart), cross-dissolved by
`hero.js`. The navbar is part of the composition - the hero pulls itself up
under the sticky bar (`margin-block-start: calc(-1 * var(--mnav-row1))`), so the
photo runs underneath a transparent navbar while the hero is on screen, and the
bar dissolves into theme glass as the hero scrolls away (see the navbar section
below).

Invariants when touching it:

- **The height is pure CSS.** `block-size: calc(100svh - var(--luann-h))` (with a
  `100vh` fallback line above it) plus the negative margin under the navbar. No
  JS measuring: the announcement bar publishes `--luann-h`, the navbar publishes
  `--mnav-row1`, and the hero composes them. The announcement bar being absent,
  dismissed or present needs no code change.
- **The layout is a named grid, not absolutes.** Rows: copy (`1fr`) / cue+tabs /
  trust strip; the photo is the absolutely positioned backdrop. The copy row can
  never slide under the category selector, at any viewport, because the selector
  is its own row - overlap is structurally impossible.
- **The cross-dissolve is one class.** `.hero-frame.is-active` flips opacity and
  a slow `scale(1.001 -> 1.06)` Ken Burns drift; both are compositor-only.
  Switching waits for the incoming photo to decode (`img.decode()`), so the fade
  is always a real cross-dissolve, never a blank flash.
- **The image queue is sequential.** Frame 1 ships in the HTML (eager,
  `fetchpriority="high"`, preloaded with a matching `imagesrcset`); frames 2..5
  carry `data-hero-src/-srcset` and are promoted one by one after the first
  paint (`requestIdleCallback`), so the first screen never waits for five
  photographs.
- **Autoplay pauses honestly.** The 2s dwell timer restarts on every
  interaction, pauses while the visitor aims at the selector (hover), while the
  tab is hidden, while the hero is off-screen (IntersectionObserver), and is
  disabled entirely under `prefers-reduced-motion` (which also drops the drift
  and shortens the fade).
- **The tick hairline is CSS.** The active tab's progress bar is a CSS animation
  whose duration is the `--hero-dwell` custom property set once by the engine;
  restarting it is a class remove/reflow/add, no timers in JS.
- **`prefers-reduced-motion`** keeps the hero fully readable: no autoplay, no
  zoom, a 220ms plain fade, no entrance cascade.

Artwork: `public/images/hero/hero-<scene>{,@480w,@760w,@1024w}.webp` plus
`hero-<scene>.jpg` fallbacks and `hero-<scene>-thumb.webp` tab thumbnails, built
from the masters in `tools/hero-masters/` by
`python3 tools/hero-masters/build_hero_images.py`. The scene crop is anchored on
the product (`object-position`, biased further right on phones).

The hero clips its overflow (`.hero { overflow: hidden }`). Short screens shrink
gracefully: at `max-height: 800` the copy anchors to the top of its row and the
type steps down; at `max-height: 680` the paragraph and the scroll cue go; on
phones (`max-width: 760`) the selector becomes a full-width swipeable rail above
a 2x2 trust strip, and landscape phones drop the paragraph.

Verify changes without a browser:

```bash
node tools/php-wasm/render.mjs home                       # render the page with php-wasm
node tools/php-wasm/render.mjs lint                       # eval-parse every changed .php file
node tools/frontend_audit/hero_engine_test.mjs            # the crossfade engine, virtual clock
node tools/frontend_audit/navbar_engine_test.mjs          # the navbar controller, stubbed DOM
node tools/frontend_audit/css_audit.mjs                   # cascade + overflow sweep
node tools/frontend_audit/static_preview.mjs --render storage/reports/_home-render.html --port 4173
```

The last one serves the php-wasm snapshot with the real `frontend/` assets, which
is how a change can be reviewed visually without a PHP runtime: render first, then
open the preview URL.

Other home-page invariants worth keeping: the announcement bar exposes its height
as `--luann-h` and every sticky/oversized element subtracts it; the mobile
rail/drawer use `100dvh` (not `100vh`, which ends under the browser toolbar);
`body.ready` must not re-enable horizontal scrolling (`overflow-y: auto` only);
and `content-visibility: auto` sections need a `contain-intrinsic-size` close to
their real height, otherwise the document grows section by section while scrolling.

## The home bands (photo / plain)

The eight home bands alternate: a band either carries a photograph behind its
content or it is a flat surface. **Do not let two neighbouring bands both read as
photo bands** - that is exactly what happened when the category mosaic came back
and the finishes band above it was still borrowing `finish-workshop.jpg`.

| band | background |
| --- | --- |
| hero | photograph (the hero) |
| trust-bar | plain, `--ds-bg` behind a vertical veil - deliberately photo-free |
| finishes | plain: deep ink + the teal/mint glow (`.finishes-backdrop`, no `url()`) |
| categories | photograph: `images/lifestyle/categories-backdrop.jpg` |
| inspiration | plain, `--ds-surface` |
| rituals | photograph: `images/lifestyle/rituals-backdrop.jpg` |
| masterpieces | plain, `--ds-surface` + a soft radial |
| corporate | photograph: `images/lifestyle/corporate-backdrop.jpg` |

How a photo band is built - copy this shape, do not invent another one:

```css
.band-section { position: relative; background: var(--ds-bg); }
.band-section::before {            /* the photograph, pinned to the viewport */
  content: ""; position: absolute; inset: 0; z-index: 0; pointer-events: none;
  background: url('../../../images/lifestyle/<name>.jpg') center / cover fixed;
}
.band-section::after {             /* the veil, themed through --ds-bg */
  content: ""; position: absolute; inset: 0; z-index: 0; pointer-events: none;
  background: linear-gradient(180deg,
    color-mix(in srgb, var(--ds-bg) 34%, transparent),
    color-mix(in srgb, var(--ds-bg) 22%, transparent));
}
[data-theme="light"] .band-section::before { filter: brightness(1.55) contrast(0.92) saturate(0.85); }
.band-section > .container { position: relative; z-index: 1; }
@media (hover: none) { .band-section::before { background-attachment: scroll; } }
```

- The veil **must** be its own layer: brightness/contrast tuning on a combined layer
  lightens the veil as well and cancels itself out.
- The veil is built from `--ds-bg` (theme-aware), while band *tokens* are theme-fixed,
  so a photo band keeps its own text colours in both themes without extra rules.
- The categories band is the one exception: its veil is stronger across the top,
  because the heading sits over the lit cove strip of the artwork.
- `background-attachment: fixed` is the parallax (the band scrolls over a still
  image); iOS ignores it, which the fallback above degrades gracefully.
- Anything between the veils and the content needs `position: relative; z-index: 1`,
  otherwise the veil swallows it - that is why the mosaic keeps its cards on top.
- Backdrops live in `images/lifestyle/` as 1584x672 (2.36:1) or 1408x768 JPEGs,
  ~85-200 KB each, and are named `<band>-backdrop.jpg`.

### Text contrast on photo bands (WCAG AA)

A veil is a *mood* layer, not a contrast guarantee: the backdrops run from
near-black to near-white behind the same heading, and the veils (12-40%) leave
both extremes readable by neither the light nor the dark theme. Measured against
the real artwork, the section subtitles landed at 1.3:1 - invisible. So text on
a photo band never relies on the veil alone:

- A section header that floats on a photograph adds
  `.section-header-on-photo` (design system). It paints a blurred plate of
  `--ds-bg` behind the header box itself - anchored to the header, so it is
  correct at every breakpoint, and the bleed is wider than the blur transition
  so no glyph ever sits in the soft edge. The copy is then measured against a
  known surface, worst case ~5.1:1 in both themes.
- Small caps/mono labels (10px tags, codes, badges) use `--ds-accent-text`
  (teal-700 light / teal-200 dark), not `--ds-primary`: teal-500 tops out at
  4.1:1 on white and 3.4:1 on ink, which fails AA below the large-text
  threshold. `--ds-primary` stays for large display type, borders, icons and
  fills.
- Light-theme `--ds-text-muted` is neutral-600, not neutral-500: neutral-500
  measured 4.47:1 on `--ds-bg` - one hundredth under the AA line.
- The hero copy panel carries its own blurred scrim in the light theme
  (`.lfc-inner::before`): the daylight scenes are near-white exactly where the
  copy sits and the soft 0.62 scrim left the kicker at 1.7:1.

`node tools/frontend_audit/band_audit.mjs` checks all of the above against the
render order read from `home/index.php`: a plain band that starts painting a
photograph, a photo band whose backdrop stops resolving, a missing veil layer or
lifted container, and two neighbouring photo bands all fail it.

The category mosaic sits **on** that photographed band, so its tiles are cards:
a 7/5 white shot stage (`object-fit: contain`) with the name under it. Keep that
framing - the catalogue photographs are cut-outs shot on pure white, and a
full-bleed `cover` crop of one either zooms a wide fixture into a detail or
squeezes a tall one into a sliver (the same reason `product-card.css` uses a
white stage and `contain`). The tile is not a "photo tile"; if a new category
band is ever built, reuse this shape instead of reintroducing a scrim.

Each tile leads with a **different product of its own category**: `HomeController`
collects every `main` / `gallery` photograph per category (never a `drawing`,
one shot per product) and hands the view up to three shuffled paths
(`$category['shots']`). So the mosaic changes from visit to visit, and
`frontend/home/categories/categories.js` turns the stage over - on hover where
there is a pointer, on a staggered timer on touch, and only while the tile is on
screen. The category's own `image` column is the fallback when a category has no
product photographs yet.

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
