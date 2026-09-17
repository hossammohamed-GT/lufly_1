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
│   └── app.css                app shell: ambience, header, nav, search, footer
├── js/
│   └── app.js                 bootstrap: theme, modal, sticky header, search, language
├── home/                      one folder per home section
│   ├── hero-slider/           hero-slider.css + hero-slider.js
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
$view->pushStyle('frontend/home/finishes/finishes.css');
$view->pushScript('frontend/home/finishes/finishes.js');
?>
<section class="band finishes-section" id="finishes"> ... </section>
```

`core/View/View.php` exposes `pushStyle()`, `pushScript()`, `styles()` and `scripts()`;
the frontend layout prints `style.css` → `app.css` → component styles in `<head>` and
`app.js` → component scripts before `</body>`. Duplicates are ignored, so a component can
be included twice safely.

## 4. JavaScript conventions

* `frontend/js/app.js` holds shared behaviour (theme, modal, sticky header, language
  dropdown, instant search). Component scripts hold their own behaviour only.
* **Scripts never write colors.** Generated markup uses CSS classes from the component
  or `app.css` instead of inline styles.
* All client-side URLs are built from `document.documentElement.dataset.base`, which the
  layout fills from `url('/')`, so search links and finish images work from any
  sub-directory and behind a proxy.
* Interactive state is expressed with classes (`is-open`, `is-active`, `is-scrolled`) and
  `data-*` hooks; the hero slider respects `prefers-reduced-motion`.

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
| Raw colors (`#hex`, `rgb()`, `hsl()`, color names) outside `style.css` | 0 |
| Undefined CSS variables in `frontend/**/*.css` | 0 |
| Classes used in PHP/JS with no CSS rule | 2 intentional modifiers (below) |
| Inline `style` attributes in views | 1 file — `components/flag.php` (national flag colors) |
| PHP syntax (tree-sitter parse of all 273 PHP files) | clean |
| JavaScript syntax (`node --check`) | clean (`app.js`, `hero-slider.js`, `finishes.js`, `admin.js`) |
| Translation keys used in views missing from `en`/`tr`/`cs` | 0 |
| `asset('…')` targets missing on disk | 0 |

Sizes: `style.css` 1 010 lines, 14 component stylesheets, 2 868 CSS lines in total;
`app.js` 223 lines, component scripts 230 lines.

Remaining raw numeric values in component files are component geometry (widths, heights,
grid templates, transforms, z-index, expressive transition durations) plus the fluid
display clamps behind `--text-display-*`. They are intentional and reviewed.

Two markup hooks are kept on purpose:

* `lufly-header-monolith` — legacy alias on the header element, kept so external styles
  and the shared branch keep working.
* `deante-wishlist-link` — modifier that composes with the styled `.deante-icon-link`.

## 7. Migration notes

* The design system file, the app shell and every component folder are new; the previous
  `frontend/design-system/*.css` set, `frontend/js/tidal-monolith.js`,
  `theme-switcher.js`, `modal.js`, `hero-scenes.js`, `admin/assets/`, `atlas.html` and the
  duplicated `public/frontend/` tree were removed.
* Asset URLs still work from the project folder (`http://localhost/lufly_1/`): the root
  `.htaccess` serves real files first and falls back to `public/`, `server.php` mirrors
  that behaviour for `php -S`, and CSS references images with relative paths.
* Layouts, component markup and route names were not renamed, so controllers, modules and
  the shared feature branch keep working unchanged.
