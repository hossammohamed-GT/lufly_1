# Design System — Tidal Monolith (Direction 01 / 10)

The complete visual identity lives in **one file**:

```
frontend/design-system/style.css
```

192 custom properties, 135 component classes, 26 numbered sections. Every color,
typeface, spacing step, radius, shadow, motion curve and core component in the project
resolves through this file. **No other stylesheet may declare a color value** — no hex,
`rgb()`, `hsl()` or named colors. Component files must use the tokens.

Colour is the identity layer; geometry (radius, border, component-specific widths) is a
supporting layer and stays as designed.

## 1. File map

| Section | Content |
| --- | --- |
| 01 | Brand palette — fixed in every theme |
| 02 / 03 | Semantic tokens — light mode / dark mode |
| 04 | Band tokens — dark architectural surfaces that stay dark in both themes |
| 05 | Media tokens — text and scrims over photography and video |
| 06 | Typography roles |
| 07 | Spacing scale (4px base unit) |
| 08 | Radius + border tokens (supporting, not identity) |
| 09 / 09b | Elevation / stacking order |
| 10 | Motion tokens |
| 11 | Base and reset |
| 12 | Layout — container, grid, stack, section rhythm |
| 13 | Icons |
| 14 | Surfaces — glass, panels, cards |
| 15 | Buttons |
| 16 | Form controls |
| 17 | Feedback — alerts, badges, states |
| 18 | Data — tables, specifications, pagination |
| 19 | Overlays — modal, dropdown |
| 20 | Switchers — theme + language |
| 21 / 21b | Motion utilities / direction (RTL) |
| 22 – 24 | Accessibility, responsive rules, reduced motion |
| 25 | Navigation chrome tokens — "Machined Glass" (`--ds-nav-*`) |

## 2. Brand palette (fixed, never themed)

| Token | Value | Role |
| --- | --- | --- |
| `--lu-teal-50 … --lu-teal-900` | `#e6f4f4` … `#082e32` | LUFLY Teal ramp |
| `--lu-teal-500` | `#1c8b8b` | **primary signature** — used in both themes |
| `--lu-teal-600` | `#147174` | primary hover |
| `--lu-deep-teal` | `#143f43` | light-mode secondary |
| `--lu-mint` | `#7fe2d0` | dark-mode secondary / band accent |
| `--lu-ink-900 … --lu-ink-100` | `#0a1414`, `#101e1d`, `#162928`, `#2d4843`, `#9cb5ae`, `#eff7f3` | dark surfaces + muted text |
| `--lu-neutral-0 … --lu-neutral-900` | `#ffffff`, `#f3f7f5`, `#e9f1ee`, `#d3deda`, `#b3c4bf`, `#8aa09a`, `#617674`, `#4f6360`, `#3a4a48`, `#24312f`, `#102a2a` | light surfaces + text |
| `--lu-border-light` | `#c8d6d1` | border reference |
| `--lu-success` / `--lu-warning` / `--lu-danger` / `--lu-info` | `#4d9077` / `#c58a32` / `#c64b4b` / `#3b7a9a` | light-mode semantics |
| `--lu-success-dark` / `--lu-warning-dark` / `--lu-danger-dark` / `--lu-info-dark` | `#78ce9c` / `#e7b658` / `#f07b7b` / `#8cc9ee` | dark-mode semantics |

`#1c8b8b` is the primary in **every** direction: actions, focus, links, active states and
brand surfaces. Secondary colours create personality, semantic colours protect clarity.

## 3. Semantic tokens

| Token | Light (`--lu-…`) | Dark (`--lu-…`) |
| --- | --- | --- |
| `--ds-bg` | `neutral-50` | `ink-900` |
| `--ds-surface` | `neutral-0` | `ink-800` |
| `--ds-text` | `neutral-900` | `ink-100` |
| `--ds-text-muted` | `neutral-500` | `ink-500` |
| `--ds-border` | `border-light` | `ink-600` |
| `--ds-primary` | `teal-500` | `teal-500` (fixed) |
| `--ds-secondary` | `deep-teal` | `mint` |
| `--ds-success` / `--ds-warning` / `--ds-danger` / `--ds-info` | light hues | dark hues |

Translucent tokens are derived with `color-mix()` from palette values, so an overlay can
never introduce a new hue: `--ds-primary-soft`, `--ds-primary-tint`, `--ds-primary-glow`,
`--ds-primary-line`, `--ds-glass-bg(-strong)`, `--ds-scrim(-soft/-strong)`,
`--ds-shadow-color(-soft/-strong)`, `--ds-focus-color`, `--ds-band-*`, `--ds-media-*`.

### Context families

| Family | Purpose |
| --- | --- |
| `--ds-band-*` | dark architectural bands (trust pillars, finish selector, footer) that stay dark in both themes |
| `--ds-media-*` | text and scrims over photography (hero cinema, category mosaic) |
| `--ds-nav-*` | navbar chrome: slab glass, field, sheen, groove, hairline, knurl, etch, blueprint line, scrim, fade, shadow, blur, chamfer, motion |
| `.band` | utility class that switches a section and its `.section-tag` / `.section-title` / `.section-subtitle` / `.separator` into band tokens |

## 4. Typography

`Arial Narrow` (`--font-display`) for display, `Inter` (`--font-body`) for utility text,
loaded from Google Fonts with preconnect in both layouts and system fallbacks.

* Scale: `--text-caption` (11px) → `--text-5xl` (52px), weights `--weight-regular` → `--weight-black`.
* Fluid display ramp: `--text-display-sm|md|lg`.
* Tracking: `--tracking-tight|normal|wide|wider|widest`; leading: `--leading-tight|snug|normal|relaxed`.
* Role helpers: `.type-display`, `.type-h1…h4`, `.type-body`, `.type-small`, `.type-caption`, `.eyebrow`.

## 5. Spacing, radius, elevation, motion, targets

* Space: 4px base (`--space-unit`), `--space-0 … --space-24` (0/4/8/12/16/20/24/28/32/40/48/56/64/80/96px)
  plus `--section-gap`, `--container-max`, `--container-narrow`, `--container-gutter`,
  `--grid-columns`, `--grid-gutter`.
* Radius: `--radius-xs|sm|md|lg|xl|full|pill` — supporting tokens, not identity rules.
* Borders: `--border-hairline`, `--border-standard`.
* Elevation: `--shadow-1|2|3`, `--shadow-soft`, `--shadow-hover`; focus `--focus-ring`,
  `--focus-ring-width`.
* Motion: `--motion-pulse` (2s ease-in-out), `--motion-drift` (2.2s), `--motion-signal`
  (1.8s steps), `--motion-orbit` (2.8s linear), `--ease-out`, `--ease-in-out`,
  `--duration-fast|base|slow`, `--transition-smooth`, with matching `ds-*` keyframes and a
  global `prefers-reduced-motion` guard.
* Stacking: `--z-base|header|dropdown|modal|skip`. Accessibility: `--tap-target` (44px),
  WCAG AA contrast, visible focus ring on every surface.

## 6. Core components in style.css

Layout `.container`, `.container-narrow`, `.grid-*`, `.stack*`, `.section*`, `.row-*`,
`.page-header`, `.header-titles` · buttons `.btn` + `.btn-primary|secondary|ghost|danger|
glass|success|icon|lg|sm|block` and the legacy-markup aliases `.btn-primary-teal`,
`.btn-secondary-glass`, `.btn-whatsapp`, `.btn-whatsapp-lg` · forms `.field`, `.field-label`,
`.field-hint`, `.field-error`, `.input`, `.select`, `.textarea`, `.check-row`, `.switch` ·
surfaces `.card*`, `.glass-panel(-strong)`, `.section-surface`, `.pill`, `.badge*` ·
feedback `.alert-*`, `.state-*`, `.empty-state` · data `.table`, `.pagination`,
`.page-link` · overlays `.modal*`, `.dropdown*` · switchers `.theme-switcher`,
`.lang-switcher`, `.lang-link`, `.theme-icon*` · icons `.icon*` · identity helpers
`.product-sku-badge(-lg)` · accessibility `.visually-hidden`, `.skip-link`.

## 6b. Navbar tokens ("Machined Glass")

| Token | Role |
| --- | --- |
| `--ds-nav-glass` / `--ds-nav-glass-dense` | slab and panel tint (translucent at rest, dense once scrolled) |
| `--ds-nav-field` | inset search field surface |
| `--ds-nav-sheen` / `--ds-nav-edge` | specular top edge and inner corner light |
| `--ds-nav-groove` / `--ds-nav-hairline` / `--ds-nav-knurl` / `--ds-nav-etch` | machined detail: brushed grain, separations, knurled strips, etched labels |
| `--ds-nav-line` / `--ds-nav-line-soft` / `--ds-nav-line-glow` | the crooked blueprint line, its resting state and its signal trace |
| `--ds-nav-scrim` / `--ds-nav-fade` / `--ds-nav-shadow` | sheet scrim, scroll fade and panel elevation |
| `--ds-nav-chamfer`, `--ds-nav-ease`, `--ds-nav-fast|base|slow` | chamfered geometry and the motion language of the header |

The header component is `frontend/components/navbar/navbar.css` (paired with
`navbar.js` and `resources/views/components/navbar.php`). It owns its row heights as
component-local custom properties (`--mnav-row1`, `--mnav-row2`, `--mnav-mark-w`) — geometry,
never colour.

## 7. Rules for contributors

1. **No raw colors** outside `style.css`. If a value is missing, add a token there.
2. Colors, typefaces, motion and elevation always come from tokens. Component files may
   tune their own geometry (paddings, gaps, widths) when the design needs a value that is
   not on the scale — see the audit in `docs/Frontend-Structure.md`.
3. Off-brand hues are not allowed: gold accents use the warning hue
   (`--ds-band-accent`, `--ds-warning`), WhatsApp green uses `--ds-success`.
4. Country flags in `resources/views/components/flag.php` keep their national colours —
   they are third-party identity marks, not design tokens.
5. Prefer semantic tokens (`--ds-*`) over palette tokens (`--lu-*`) in components.

## 8. Theming

`<html data-theme="light|dark">` is set before first paint by the inline script in
`resources/views/layouts/frontend.php`, then kept in sync by `frontend/js/app.js`
(`localStorage` key `lufly-theme`). Adding a theme means adding one `[data-theme="…"]`
block in `style.css`.
