# Frontend audit harnesses

No browser is available in this repo's sandbox (Chrome/Chromium downloads are
blocked, and no system browser can be installed), so the frontend is verified
with small Node harnesses instead. They are plain ES modules with **no
dependencies** — run them straight from the repo root.

## `hero_fit_test.mjs` — home hero sizing

```bash
node tools/frontend_audit/hero_fit_test.mjs                  # 9 simulated devices, exits 1 on regression
node tools/frontend_audit/hero_fit_test.mjs --all            # same, explicit
node tools/frontend_audit/hero_fit_test.mjs --legacy-viewport # pretend the browser has no svh support
node tools/frontend_audit/hero_fit_test.mjs --json           # machine-readable rows (used by hero_report)
node tools/frontend_audit/hero_fit_test.mjs --file path.js   # test another build of the hero script
```

It loads `frontend/home/hero-cinema/hero-cinema.js` into a stubbed DOM (a device
profile, a fake `getBoundingClientRect`, a 100svh-style probe, a timer queue) and
replays the browser behaviour that used to break the hero:

- **scroll + `resize`** — the reported bug: the old script re-fitted from a client
  rect, so every resize while scrolling added the scroll offset to the height
  ("grew while scrolling 378 → 646").
- **a burst of twelve resizes** while the viewport height wobbles (mobile URL bar)
  — the height must settle on one value.
- **`orientationchange` / hidden window** — no bogus height.
- **the open screen** — at the top of the page with the browser toolbars retracted
  the hero must fill the newly visible strip, but while scrolled it must hold the
  small-viewport height so nothing under the reader shifts.
- **rotation** — every portrait phone is also measured flipped to landscape, where
  the viewport is suddenly much shorter (and the landscape-phone CSS block applies):
  the hero must be re-measured, not carried over, and if the script hands the very
  short viewports (<200px of space) to the CSS fallback, that fallback has to fit.
- **the phone copy** — the model reproduces the compact phone type scale and checks
  the copy still fits inside the full-height hero.
- **the phone artwork** — the photo fills the hero edge to edge, so the box is tall
  and the crop is what decides whether it looks cramped. The harness measures how
  much of the portrait artwork (800x1072 dark / 768x1290 light, built by
  `tools/media_audit/hero_portrait_crops.py`) a phone box actually shows and fails
  under 65%.
- **window round trips** — the simulated window can be resized (`m.resizeTo(w, h)`
  moves the viewport, the media queries and fires `resize`), and four round trips
  assert that the *applied* background follows the breakpoint both ways: a phone
  window must get `-p`, a desktop width must get the landscape master, in any order.
  That is the "the picture is suddenly zoomed after I go back to the big screen"
  bug, and it is caught by reading `background-image` off the scenes.
- the result must fit the first screen, cover what the copy needs, and stay under
  the portrait artwork cap (`1.35 × width`) that keeps a phone photo from being
  zoomed into a close-up.

Exit code 1 means at least one device failed; the printed table lists the hero
height, the height the copy needs and the ceiling for each device.

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
| hero-cinema | photograph (`heroc-*`, chosen at runtime) |
| trust-bar | plain |
| finishes | plain (ink + glow, no `url()`) |
| categories | photograph (`categories-backdrop.jpg`) |
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
