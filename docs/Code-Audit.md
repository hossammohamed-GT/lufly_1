# Code Audit — full-repo review (2026-09-29)

A complete review of every tracked file: all 395 PHP files, 23 JS files,
26 stylesheets, both seeder JSON datasets, the routing/config/language wiring,
the database schema against the code that queries it, and every public asset
reference. The verdict: **the codebase is sound** — a custom PSR-4 framework
with prepared statements everywhere, real CSRF/session hardening, and a
disciplined module layout. The audit found **12 real defects, all fixed in
this commit** (mostly drift: retired UI still referenced, stale tooling,
missing i18n keys). Nothing in the catalog data or the product images was
touched.

## How the audit ran

| Layer | What covered it |
|---|---|
| PHP syntax (every file) | `node tools/php-wasm/syntax_check.mjs` — the PHP engine itself parses all 395 files (`token_get_all` with `TOKEN_PARSE`) plus brace/paren/bracket balance |
| Wiring (cross-references) | `python3 tools/code_audit/xref_check.py` — trans/config/route/view/handler/class/SQL-table/asset/middleware references all resolve; language files carry identical keys in en/tr/cs |
| Frontend behaviour | `hero_engine_test`, `navbar_engine_test`, `finishes_lab_test` (jsdom), `band_audit`, `css_audit`, `phone_preview`, `locale_data_test` |
| Server rendering | `tools/php-wasm/render.mjs` for home in en/tr/cs and the catalogue |
| Manual review | Router, View, Autoloader, Container/Application, Session, CsrfGuard, SecurityHeaders, UploadService, Database\Connection, heuristic scan for classic PHP bug patterns (loose `== 0`, assignment-in-condition, `eval`, unguarded `extract`, md5-for-passwords, user `unserialize`) |

The two new tools are permanent: re-run them after any change.

```bash
node tools/php-wasm/syntax_check.mjs          # all php files parse
python3 tools/code_audit/xref_check.py        # 0 failures, 0 warnings expected
```

## Findings and fixes

1. **`band_audit` still expected the retired `hero-cinema` band** — the hero
   was redesigned (4 scenes, `hero-<scene>{,-light,-thumb,-light-thumb}.webp`)
   but the audit's band map and its template checks still pointed at
   `resources/views/home/hero-cinema/hero-cinema.php` and `heroc-*` artwork.
   It reported "hero: not listed in this audit". Fixed: the tool now audits
   `resources/views/home/hero/hero.php` and verifies all 16 scene masters.

2. **The finishes lab carried a whole retired spec-sheet UI in its code.**
   The markup ships a scene, a callout, a rail, CTAs, a factory strip and a
   compare drawer — but `finishes.js` still looked up seven elements that no
   template has ever rendered (`finish-title/-index/-tag/-base/-coating/
   -cartridge/-aerator`, null-safe so nothing crashed), kept spec fields
   (base brass, coating, cartridge, aerator) nobody displays, a `pad()`/ORDER
   counter for a counter that does not exist, and hotspot handlers for
   `.fs-hotspot` elements that were never in the view. `finishes.css` kept
   127 lines styling `.fs-specs/.finish-title/.finish-counter/.finish-spec-
   pill/.finish-tech-specs/.tech-spec-item/.fs-right/.fs-hotspot`. Removed
   (81 lines of JS, 131 lines of CSS); docblocks updated to match reality.

3. **`finishes_lab_test.cjs` could never have passed.** It hand-rendered a
   view version that does not exist (spec sheet, counter, three hotspots) and
   simulated the old rendition-ladder `<picture srcset>` markup that
   `responsive-image` no longer emits (owner decision 2026-09-29: plain
   full-quality `<img>`). Rewritten against the real component: 36
   assertions, all passing — rail, cross-fade, callout, deep link, drawer,
   chevrons, entrance choreography, plus a guard that the retired UI styles
   stay out of the CSS.

4. **`phone_preview.mjs` crashed on cache-busted asset URLs** — it read
   `frontend/design-system/style.css?v=…` from the rendered page verbatim.
   It also hard-required `hero_fit_test.mjs`, a sandbox-only one-off that is
   not in the repo. Fixed: query strings are stripped when resolving files;
   the caption numbers degrade gracefully when the fit harness is absent.

5. **`trans('common.file')` and `trans('errors.unauthorized')` matched no
   key in any locale** — both call sites carried English `??`/`?:` fallbacks,
   so visitors saw untranslated strings in tr/cs. Keys added to all three
   locales (`Dosya`/`Soubor`, `Bu işlem için yetkiniz yok.`/`K provedení
   této akce nemáte oprávnění.`).

6. **`config('app.key')` was read in three places but never defined** —
   AiGuard, BoxService and FavoriteService HMAC visitor IPs with
   `config('app.key', 'lufly')`, and the fallback was always in charge
   because `config/app.php` had no `key` entry while `.env.example` ships
   `APP_KEY`. Wired: `'key' => env('APP_KEY', 'lufly')`.

7. **`tools/php-wasm/render.mjs` targeted a dead `@php-wasm/node` layout**
   (pre-3.x `node_modules/@php-wasm/node/asyncify`) and omitted the
   `emscriptenOptions.processId` the 3.x loader requires. Fixed to
   `node-8-3/asyncify`, mirroring `lint.mjs`; home renders clean in all
   three locales again.

8. **The root `images -> public/images` symlink was deleted as "unused" in
   the previous cleanup and `band_audit` broke** (its photo-band checks
   resolve `images/lifestyle/…` through it, by documented design). Restored —
   it is load-bearing for the audit tooling; the stylesheet paths and the
   checks see the same files either way.

*(Items 9–12 were checker-tooling defects found while building the audit
itself, not app defects: false positives on concat translation keys,
PSR-4 case fallback, group route-name prefixes and comment prose inside SQL
scans — all resolved inside `xref_check.py`.)*

## Verified sound (no action needed)

- **SQL injection**: every query goes through PDO prepared statements with
  `ATTR_EMULATE_PREPARES => false`; no string-interpolated SQL found.
- **Uploads**: extension allow/block lists, `finfo` MIME sniffing, embedded
  `<?php` rejection, SVG sanitising, path-traversal guards on both directory
  and final `realpath` containment, random filenames.
- **CSRF / sessions**: `hash_equals` token compare, Bearer-token bypass only
  where cookie-CSRF cannot apply, same-origin AJAX check against `Origin`;
  session cookies `HttpOnly`+`SameSite=Lax` with `Secure` following the
  actual request scheme.
- **Auth**: bcrypt for passwords (cost from config), `sha1`/`md5` appear only
  as cache-key/fingerprint/lockout hashes, login lockout by e-mail and IP.
- **Routing**: names, prefixes, localized URIs (`/tr/iletisim` style) and
  every `[Controller::class, 'method']` handler resolve — 135 `route()` calls
  against 158 registered names, zero misses.
- **i18n**: 611 translation keys used across PHP and JS, present in all
  three locales; the 13 language files carry identical key sets in en/tr/cs.
- **Assets**: all 564 product images match media rows 1:1; hero (16 webp),
  finishes (18), lifestyle (10), logo/favicon all referenced; every
  `asset()/pushStyle/pushScript` path resolves.
- **Autoloading**: PSR-4 with a first-segment case fallback
  (`Database\Schema\*` → `database/schema/*`) — the migrations' casing is
  handled, not broken.
- **Frontend**: all 42 css/js component assets referenced; all JS files
  parse; all stylesheets brace-balanced; hero/navbar engine tests and the
  full jsdom finishes suite pass; layout audit clean at every viewport.

## Re-running everything

```bash
npm install --no-save @php-wasm/node @php-wasm/universal jsdom   # tooling only
node tools/php-wasm/syntax_check.mjs                             # 395 files
python3 tools/code_audit/xref_check.py                           # 0 / 0
node tools/php-wasm/render.mjs home en                           # + tr, cs
node tools/frontend_audit/finishes_lab_test.cjs                  # 36/36
node tools/frontend_audit/hero_engine_test.mjs                   # etc.
python3 tools/frontend_audit/locale_data_test.py
```
