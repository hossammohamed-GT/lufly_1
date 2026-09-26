# Link targets

**The rule: a link opens in a new tab only when it leaves lufly.tr.** Anything
that stays on our own site — the home page, a category, a product, the planner,
a language switch, an admin screen — opens in the same tab.

Opening our own pages in a new tab costs the visitor the browser Back button,
the loading state and the sense of a single site; it also defeats the loader's
instant page transitions, which can only take over a same-tab navigation.

## Why it used to happen

The navbar decided this with:

```php
str_starts_with($link['url'], 'http') ? 'target="_blank" rel="noopener"' : ''
```

That test is meaningless here, because `route()` builds **absolute** URLs
(`https://lufly.tr/en/products`) — every internal link starts with `http`, so
every internal link got `target="_blank"`. The scheme says nothing about who
owns the page; only the host does.

## The helpers

Two functions in `core/Foundation/helpers.php` are now the single source of
truth:

| helper | returns |
|---|---|
| `is_external_url($url)` | `true` when the URL points at another host |
| `external_link_attrs($url)` | `target="_blank" rel="noopener noreferrer"`, or `''` for our own pages |

Use the second one in views, so the `href` and the attributes cannot drift
apart:

```php
<a href="<?= e($url) ?>" <?= external_link_attrs($url) ?>>…</a>
```

### What counts as internal

- Absolute URLs on our own host (`https://lufly.tr/en/products`) — the common
  case, since `route()` is absolute.
- Relative and root-relative paths (`/en/contact`).
- Bare anchors (`#finishes`).
- Non-navigating schemes that never open a page: `mailto:`, `tel:`,
  `whatsapp:`, `data:`.

### What counts as external

- Any other host: `wa.me`, Google Maps, a partner site, a link an editor typed
  into an announcement.

## Where the exceptions live

Three external link builders are in JavaScript rather than PHP, and keep their
own `_blank` because the URL is external by construction:

- `frontend/assistant/assistant.js` — the WhatsApp support link.
- `frontend/pages/contact.js` — `window.open()` for the WhatsApp enquiry.
- `frontend/components/loader/loader.js` and `frontend/js/app.js` only *read*
  `link.target === '_blank'` to skip links they must not intercept. That check
  stays correct: it now matches exactly the links that leave the site.

## Adding a link

Point it at `route()` / `url()` and add `<?= external_link_attrs($url) ?>`. Do
not hand-write `target="_blank"` on an internal URL, and do not test the scheme.

## Checking

Render a page and count what still opens a new tab — the list should be only
`wa.me` and Google Maps:

```bash
grep -o '<a[^>]*target="_blank"[^>]*>' page.html
```
