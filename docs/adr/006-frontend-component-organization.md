# ADR 006 - Frontend component organization

* Status: Accepted
* Date: 2026-09-17

## Context

The frontend shipped as one large stylesheet (`tidal-monolith.css`, ~2 300 lines) plus
token files and a JavaScript controller, duplicated in two folders (`frontend/` and
`public/frontend/`). Home sections, catalog, detail, auth, admin and error pages all
shared the same global CSS, so every change risked breaking unrelated screens.

## Decision

1. **One design-system file** - `frontend/design-system/style.css` owns palette,
   semantic tokens, typography, spacing, radius, elevation, motion, stacking order and
   the core components. It is the only file allowed to contain raw color values.
2. **One folder per component** - the same name is used in `frontend/<area>/<name>/`
   (`<name>.css`, `<name>.js`) and `resources/views/<area>/<name>/<name>.php`.
   Home has eight section components; catalog, detail, login, admin and errors have
   their own folders.
3. **Assets are declared by components** - `View::pushStyle()` / `View::pushScript()`
   register files while the component renders; the layout emits them in `<head>` and
   before `</body>`. Pages therefore load only what they use.
4. **Single asset root** - `frontend/` at project root; the duplicated
   `public/frontend/` copy and the unused `hero-cinematic.css` / `hero-scenes.js` /
   `atlas.html` were removed.
5. **Off-brand colors removed** - the gold accents become the warning hue, WhatsApp
   green becomes the success hue, and every translucency is derived with `color-mix()`
   from palette colors.

## Consequences

* A section can be read, restyled or deleted in isolation.
* Class names, routes and template names were preserved, so no route or controller
  behaviour changed; only asset paths moved.
* Component CSS files may tune their own geometry, but not colors - the design system
  remains the single place to change a hue or a type ramp.
* Loading many small files is fine for local XAMPP use; a bundling step can be added
  later without touching component sources.
