# Frontend audit harnesses

No browser is available in this repo's sandbox (Chrome/Chromium downloads are
blocked, and no system browser can be installed), so the frontend is verified
with small Node harnesses instead. They are plain ES modules with **no
dependencies** — run them straight from the repo root.

## `hero_engine_test.mjs` — hero crossfade engine

```bash
node tools/frontend_audit/hero_engine_test.mjs
```

Loads the real `frontend/home/hero/hero.js` into a stubbed DOM with a virtual
clock (25ms slices, microtasks drained between slices, like a real event loop)
and asserts the invariants the hero design depends on:

- **boot** — frame 1 active, tab 1 pressed, others released;
- **image queue** — frames 2..5 promoted strictly one after another
  (`[1,2,3,4]`), the eager first frame never re-fetched;
- **autoplay** — bathroom → kitchen → shower in order, each scene holding its
  2000ms dwell through the 1050ms cross-dissolve;
- **click** — switches at once and restarts the dwell from the clicked scene
  (wrapping back to bathroom);
- **hover** — autoplay pauses while the visitor aims at the selector, resumes
  on leave;
- **reduced motion** — no autoplay at all, clicks still switch.

Exit code 1 on the first failed assertion.

## `navbar_engine_test.mjs` — navbar controller

```bash
node tools/frontend_audit/navbar_engine_test.mjs
```

Loads the real `frontend/components/navbar/navbar.js` into a stubbed DOM with a
virtual scroll and asserts: the progressive `--nav-glass` while the hero scrolls
away (overlay mode) vs. the plain 8px threshold on other pages; the expanding
search (open, focus, live results from a stubbed API, Escape); the language menu
and the "More" fold excluding each other; and the bloom sheet (open, scroll lock,
scrim close).

## `css_audit.mjs` — cascade / layout sweep

```bash
node tools/frontend_audit/css_audit.mjs                       # default 320/390/768/1280
node tools/frontend_audit/css_audit.mjs --widths 360,412 --heights 640,800
node tools/frontend_audit/css_audit.mjs --widths 280,320,568 --heights 320,568,844
```

A miniature cascade evaluator: it reads `frontend/design-system/style.css` plus
every stylesheet reachable from the home page in order, resolves `@media`,
`@supports`, `min()`, `clamp()` and `var()`, matches class selectors exactly,
skips hover/`::after` state rules, and reports the values that matter for layout
(grid columns, fixed heights, aspect ratios, the sticky chrome, the hero).

It answers "does anything overflow, collapse to zero or keep a desktop number on
a phone" without a rendering engine.

Besides the curated table it runs a generic horizontal-overflow scan over every
rule that applies at the tested viewport: fixed `width` / `min-width` /
`inline-size` / `flex-basis` values larger than the screen, and `100vw` next to
horizontal padding (the classic 100vw + gutter scrollbar). Tall art that only
fills the screen *below* the fold is reported as a note, not as a defect.

## `band_audit.mjs` — the home bands: photo or plain

```bash
node tools/frontend_audit/band_audit.mjs          # exits 1 on drift
node tools/frontend_audit/band_audit.mjs --json
```

The home page alternates photographic bands with flat ones, and the order is read
from `resources/views/home/index.php` rather than hardcoded. Each band's own
stylesheet is then checked against the intended background:

| band | background |
| --- | --- |
| hero | photograph (`images/hero/hero-<scene>.webp`, responsive srcset) |
| trust-bar | plain |
| finishes | plain (ink + glow, no `url()`) |
| categories | photograph (`categories-backdrop.jpg`), with product-card framed tiles on it |
| inspiration | plain |
| rituals | photograph (`rituals-backdrop.jpg`) |
| masterpieces | plain |
| corporate | photograph (`corporate-backdrop.jpg`) |

It fails when a "plain" band starts painting a photograph, when a photo band's
backdrop stops resolving to a real file (`images/` is a symlink to
`public/images/`, both spellings are checked), when a photo band loses its
`::before` layer, its lifted container (`z-index: 1`) or the iOS
`background-attachment` fallback, and when two **neighbouring** bands are both
photographic — the page then reads as one long image.

## `static_preview.mjs` — the home page in a browser, no PHP

```bash
# snapshot first (sandbox only): node /tmp/phpwasm/run/render_home.mjs
node tools/frontend_audit/static_preview.mjs --render storage/reports/_home-render.html --port 4173
```

Serves a server-rendered home snapshot at `/` together with the real `frontend/`
CSS + JS and `images/`, so the page can be reviewed live (theme toggle, hero
crossfade and hover states all run; only PHP routes and the search API 404). The
snapshot's `http://localhost` URLs are turned into root-relative paths, so the
browser talks to the preview host and never to localhost. Every missing file is
logged as a 404.

## `phone_preview.mjs` — device preview in a real browser

```bash
# needs a server-rendered snapshot first (sandbox only):
#   php-wasm run/render_home.php > storage/reports/_home-render.html
node tools/frontend_audit/phone_preview.mjs --render storage/reports/_home-render.html \
     --out storage/reports/hero-phones.html
```

Builds one real `<iframe>` per device (Galaxy Fold, iPhone SE, iPhone 12, iPhone 15
Pro Max and a landscape phone), each holding the real page: the stylesheets are
inlined, the scripts are loaded, and the frames are sized to the device viewport, so
the media queries and the hero script run for real. The captions come from
`hero_fit_test.mjs --json`, so the sheet and the checks always agree. Use it when
someone has to *see* the result - it is the only view this sandbox cannot produce
itself.

## `hero_report.mjs` — the visual sheet

```bash
node tools/frontend_audit/hero_report.mjs                     # -> storage/reports/hero-fit.html
node tools/frontend_audit/hero_report.mjs --out /tmp/x.html
```

Renders one scaled frame per simulated device (viewport box, hero height, the
height the copy needs, the artwork cap) plus the hero artwork contact sheet. It
runs `hero_fit_test.mjs --json`, so the sheet always shows the same numbers the
checks assert on.

## Related one-off scripts (not in the repo)

Server-side behaviour is verified with `php-wasm` (`@php-wasm/node`, installed in
the sandbox) by mounting the repo and rendering real requests: the home page in
all three locales, the media sections, the product API. Those scripts live in the
sandbox only, because they need the host filesystem mount.
