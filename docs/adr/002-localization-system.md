# ADR 002 - Localization: Static Files + Dynamic Tables + Translated Routes

## Context

The platform serves English, Turkish and Czech today and unlimited languages later -
both UI strings and user-managed content, with translated URLs (`/en/products`,
`/tr/urunler`, `/cs/produkty`).

## Problem

A single mechanism cannot cover (a) developer-owned UI copy, (b) editor-owned content
and (c) SEO-friendly localized URLs, while keeping "add a language without code
changes".

## Decision

Three layers:

1. **Static translations** - `resources/lang/{locale}/{file}.php` arrays, accessed via
   `trans('file.key')` with fallback locale.
2. **Dynamic translations** - one `{entity}_translations` table per translatable
   entity, keyed `(entity_id, locale)`, managed by `LocalizationService`.
3. **Localized routing** - `resources/lang/{locale}/routes.php` maps route keys to URI
   templates; `Router::localized()` resolves them per locale at dispatch and URL
   generation time.

Locale set lives in `config/localization.php` + `languages` table.

## Alternatives

1. **gettext** - rejected: tooling friction on XAMPP/Windows, weaker CMS workflow.
2. **Single JSON translations table for everything** - rejected: hot-path UI strings
   would hit the DB on every render.
3. **Query-param locale only (`?lang=`)** - rejected: poor SEO; kept only as a
   fallback switcher mechanism.

## Consequences

- New language = new lang folder + config entry + DB row; zero code edits.
- Route matching performs one array lookup per localized route per request (cheap).
- Translation fallback chain (locale → fallback → key) keeps missing strings visible
  instead of crashing.
