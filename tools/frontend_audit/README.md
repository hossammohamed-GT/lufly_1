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
- the result must fit the first screen, cover what the copy needs, and stay under
  the portrait artwork cap (`1.35 × width`) that keeps a phone photo from being
  zoomed into a close-up.

Exit code 1 means at least one device failed; the printed table lists the hero
height, the height the copy needs and the ceiling for each device.

## `css_audit.mjs` — cascade / layout sweep

```bash
node tools/frontend_audit/css_audit.mjs                       # default 320/390/768/1280
node tools/frontend_audit/css_audit.mjs --widths 360,412 --heights 640,800
```

A miniature cascade evaluator: it reads `frontend/design-system/style.css` plus
every stylesheet reachable from the home page in order, resolves `@media`,
`@supports`, `min()`, `clamp()` and `var()`, matches class selectors exactly,
skips hover/`::after` state rules, and reports the values that matter for layout
(grid columns, fixed heights, aspect ratios, the sticky chrome, the hero).

It answers "does anything overflow, collapse to zero or keep a desktop number on
a phone" without a rendering engine.

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
