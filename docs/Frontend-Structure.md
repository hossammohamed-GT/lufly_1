# Frontend Structure (Component-Based Organization)

The frontend follows one component folder per UI block — the same naming is reused in
CSS, JavaScript and the PHP view, so a section can be traced end to end.

## 1. Asset roots

```
frontend/
├── design-system/
│   ├── style.css              single source of truth (tokens + core components)
│   └── logo.png               brand mark
├── css/
│   └── app.css                app shell: ambient stage + mega footer
├── js/
│   └── app.js                 bootstrap: theme + modal plumbing
├── components/                shared chrome, one folder per block
│   └── navbar/                navbar.css + navbar.js ("Machined Glass" header + side spine)
├── home/                      one folder per home section
│   ├── hero-cinema/           hero-cinema.css + hero-cinema.js
│   ├── trust-bar/             trust-bar.css
│   ├── finishes/              finishes.css + finishes.js
│   ├── categories/            categories.css
│   ├── inspiration/           inspiration.css
│   ├── rituals/               rituals.css
│   ├── masterpieces/          masterpieces.css
│   └── corporate/             corporate.css
├── products/
│   ├── catalog/               catalog.css   (products.index)
│   └── detail/                detail.css    (products.show)
├── auth/
│   └── login/                 login.css
├── admin/
│   ├── admin.css              admin shell
│   └── admin.js               admin interactions
└── errors/
    └── errors.css             401/403/404/422/500
```

Brand imagery stays in `public/images/` and is referenced through `asset('images/...')`
(the root `.htaccess` and `server.php` both serve it). CSS files reference images with
paths relative to the stylesheet, never with a leading slash, so the project also works
from a sub-directory such as `http://localhost/lufly_1/`.

## 2. Views mirror the asset folders

```
resources/views/
├── layouts/
│   ├── frontend.php           loads style.css + app.css, then pushed component styles
│   └── admin.php              loads style.css + admin/admin.css
├── components/                shared partials (navbar, footer, alert, flag, modal, …)
│   └── navbar.php             machined-glass header: rail, index drawer, bloom sheet
├── home/
│   ├── index.php              composes the eight section components
│   ├── hero-slider/hero-slider.php
│   ├── trust-bar/trust-bar.php
│   ├── finishes/finishes.php
│   ├── categories/categories.php
│   ├── inspiration/inspiration.php
│   ├── rituals/rituals.php
│   ├── masterpieces/masterpieces.php
│   └── corporate/corporate.php
├── admin/dashboard.php
└── errors/
```

`HomeController` renders `home.index`; every other page keeps its existing route and
template name (`products::index`, `products::show`, `auth`, `errors.404`, …).

## 3. Asset loading per component

Each component declares what it needs and the layout renders it — no page-wide bundles:

```php
<?php
/** @var Core\View\View $view */
$view->pushStyle('frontend/components/navbar/navbar.css');
$view->pushScript('frontend/components/navbar/navbar.js');
?>
<header class="mnav" data-navbar> ... </header>
```

`core/View/View.php` exposes `pushStyle()`, `pushScript()`, `styles()` and `scripts()`;
the frontend layout prints `style.css` → `app.css` → component styles in `<head>` and
`app.js` → component scripts before `</body>`. Duplicates are ignored, so a component can
be included twice safely.

## 4. JavaScript conventions

* `frontend/js/app.js` holds shared behaviour only (theme switch, modal plumbing) so the
  admin panel keeps working. Header behaviour — scroll state, index drawer, bloom sheet,
  search overlay, instant search and the language menu — lives in
  `frontend/components/navbar/navbar.js` and loads with its component.

### Third-party assets

Component CSS is local, but a component may also push an absolute URL when a
third-party stylesheet is genuinely needed (the hero pulls Font Awesome for its icons).
The layout then:

* emits a `<link rel="preconnect">` for the origin,
* loads the stylesheet **without blocking the first paint**
  (`media="print" onload="this.media='all'"`) with a `<noscript>` fallback,
* and never routes it through `asset()`.

Icons must therefore survive a slow or unreachable CDN. The hero arrow controls paint their
glyphs as CSS chevrons and hand over to the webfont only when it is really available
(`.fa-ready`, set by `hero-cinema.js` after checking the stylesheet *and*
`document.fonts.check`). Decorative glyphs reserve `1em` so nothing shifts when the font
lands.

`View::pushPreload($href, $attributes)` lets a component start a critical fetch before the
markup is parsed; the hero preloads the first slide in both breakpoint variants with
`fetchpriority="high"` and lets the `media` attribute drop the one the device does not
need.
* **Scripts never write colors.** Generated markup uses CSS classes from the component
  or `app.css` instead of inline styles.
* All client-side URLs are built from `document.documentElement.dataset.base`, which the
  layout fills from `url('/')`, so search links and finish images work from any
  sub-directory and behind a proxy.
* Interactive state is expressed with classes (`is-open`, `is-active`, `is-scrolled`,
  `is-drawer-open`, `is-sheet-open`, `is-search-open`) and `data-*` hooks; every animated
  component respects `prefers-reduced-motion`.
* The navbar never reads layout while scrolling: scroll state and the progress line are
  painted inside `requestAnimationFrame`, and the slab keeps a fixed height so scrolling
  never triggers reflow.

## 5. Adding a new component

1. Create `resources/views/<area>/<name>/<name>.php` and register its styles/scripts
   with `pushStyle()` / `pushScript()`.
2. Create `frontend/<area>/<name>/<name>.css` (and `.js` when it needs behaviour).
3. Use only design tokens from `frontend/design-system/style.css` for color, typography,
   motion and elevation.
4. Ship a file per section: no shared "catch-all" stylesheet may grow again.

## 6. Verification performed

Re-runnable gate: `python3 tools/frontend-audit.py` (from the project root).

| Check | Result |
| --- | --- |
| Raw colors (`#hex`, `rgb()`, `hsl()`, color names) outside `style.css` | 38, all inside `home/hero-cinema/hero-cinema.css` |
| Undefined CSS variables in `frontend/**/*.css` | 0 (component-local `--mnav-*` / `--lfc-*` properties count as declared) |
| Classes used in PHP/JS with no CSS rule | Font Awesome classes in `home/hero-cinema/hero-cinema.php` — that stylesheet is never loaded |
| Inline `style` attributes in views | 1 file — `components/flag.php` (national flag colors) |
| JavaScript syntax (`node --check`) | clean |
| Translation keys used in views missing from `en`/`tr`/`cs` | `home.ticker_1…5` missing in `tr` and `cs` |
| `asset('…')` targets missing on disk | 0 |
| Stale references to removed paths | 0 outside `docs/adr/` (decision records keep their original wording) |

Sizes: `style.css` 1 065 lines, 15 component stylesheets, ~3 900 CSS lines in total;
`app.js` 105 lines, `components/navbar/navbar.css` ~1 410 lines,
`components/navbar/navbar.js` ~570 lines.

Remaining raw numeric values in component files are component geometry (widths, heights,
grid templates, transforms, z-index, expressive transition durations) plus the fluid
display clamps behind `--text-display-*`. They are intentional and reviewed.

## 7. Navbar rebuild (2026-09-18)

The marquee header was replaced by the "Machined Glass" navbar:

* `resources/views/components/navbar.php` + `frontend/components/navbar/` own the whole
  header: glass slab, instant search, utility actions, the machined **side spine**, the
  index drawer that unfolds on hover and the phone bloom sheet.
* The bar is a **single fixed-height row** (`--mnav-row1`). Everything that used to be a
  second row now lives in the spine: a vertical rail fixed to the inline-start edge with
  the crooked machined line, the quick links read downwards, a knurled foot and the
  scroll progress line. The drawer unfolds out of the spine, starting under the sticky
  bar. Below 1024px the spine is replaced by the bloom button + bottom sheet.
* The app shell offsets content by `--mnav-rail-w` (declared by the component on `:root`)
  once, on load, so nothing reflows while scrolling.
* New tokens live in section 25 of `style.css` (`--ds-nav-*`, light + dark).
* Removed with it: the legacy monolithic stylesheet in `design-system/` (53 KB of
  duplicated raw-color CSS that no view referenced) and the stale duplicate of it that
  shipped under `public/`; `home/hero-cinema/hero-cinema.css` now uses `--ds-primary` instead of the retired
  `--lufly-*` variables.
* Design review happens without PHP: `node tools/navbar-preview/serve.mjs` renders the real
  partial into a static page (dev-only harness).

## 8. Migration notes

* The design system file, the app shell and every component folder are new; the previous
  design-system CSS set, the retired JavaScript switchers, the media explorer page, the
  old admin asset folder and the duplicated public asset tree were removed. The one
  remaining legacy stylesheet in `design-system/` was deleted in the navbar rebuild —
  section 7 above.
* Asset URLs still work from the project folder (`http://localhost/lufly_1/`): the root
  `.htaccess` serves real files first and falls back to `public/`, `server.php` mirrors
  that behaviour for `php -S`, and CSS references images with relative paths.
* Layouts, component markup and route names were not renamed, so controllers, modules and
  the shared feature branch keep working unchanged.
