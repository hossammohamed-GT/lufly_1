# LUFLY Platform

Website + admin panel for **LUFLY** — a sanitary-ware brand (mixers, WCs, washbasins, shower systems, accessories).
Trilingual public catalog (**English / Türkçe / Čeština**) with a full content admin panel.

Built on a **custom, dependency-free PHP framework** (no Composer, no Laravel) so it can be uploaded to any
classic shared hosting / XAMPP and run immediately.

---

## Quick start (XAMPP / local)

1. **Import the database** — `lufly-database.sql` (repo root). phpMyAdmin → **Import** → choose the file → **Go**.
   It creates the `lufly` database, all 42 tables and all data from scratch.
2. **Copy the environment file** and point it at MySQL:
   ```dotenv
   DB_CONNECTION=mysql
   DB_DATABASE=lufly
   DB_USERNAME=root
   DB_PASSWORD=
   ```
3. Open `http://localhost/lufly_1/` — done.

> Zero-config alternative: set `DB_CONNECTION=sqlite` and the site runs directly on `database/lufly.sqlite`
> (identical content — both are shipped in the repo and kept in sync).

### PHP built-in server (no Apache)

```bash
php -S localhost:8000 server.php
```

## Requirements

- PHP **8.1+** (works with the PHP bundled in any recent XAMPP)
- PDO extension: `pdo_mysql` **or** `pdo_sqlite`
- No Composer, no Node, no build step — assets are plain CSS/JS

## Where things live

```
lufly-database.sql         MySQL export (import this on hosting)
database/lufly.sqlite      SQLite dev database (same content)
database/schema/*.php      Table definitions (source of truth for DDL)
database/migrations/       Migration files (19, all applied)
database/seeders/          Seeders (products/categories/users/roles/settings…)

core/                      The mini-framework (Router, View, PDO layer, Console, Container…)
app/Http/Controllers/      App-level controllers (Home, Contact…)
modules/<Module>/          Self-contained modules:
                           Controllers / Models / Repositories / Services /
                           Requests / Routes / Views   (11 modules)
routes/                    web.php · api.php · admin.php
resources/views/           layouts + components + page views (home sections, contact, errors)
resources/lang/<locale>/   UI translations per locale (nav.php, home.php, products.php, routes.php…)
frontend/                  Plain CSS/JS per page-section (no bundler)
public/                    Web root assets: images/, index.php, robots.txt, sitemap.xml
storage/                   logs · cache · uploads · backups   (runtime / git-ignored)
config/                    app, database, modules, localization, seo, security…
```

## The admin panel

- Login page: `/{locale}/login` (e.g. `/en/login`)
- Panel root: `/admin` (requires login)
- Modules: **Products, Media, Settings, SEO, Users, Permissions, Languages, Notifications, Announcements**
- The admin account is created by `UserSeeder` (see `database/seeders/UserSeeder.php` for the email; the password
  is bcrypt-hashed — rotate it after first login on any real deployment).

## Saved products (favorites) & e-mail

Every product can be saved with the heart on a catalogue card or on the product
page. A list belongs to an anonymous visitor: its token lives in a long-lived
cookie and travels in the e-mailed copy, so `/{locale}/favorites/{token}` reopens
the same list on any device (`/en/favorites`, `/tr/favoriler`, `/cs/oblibene`).

The list page mails the visitor one message with a card per product and a direct
link to each product page (`favorites::emails.list`), and — with
`FAVORITES_NOTIFY_ADMIN=true` — sends a lead copy to the store inbox
(`favorites::emails.admin`) whose Reply-To is the visitor. If the visitor ticks
“e-mail me whenever I save a new product”, later saves mail the updated list by
themselves (20 s apart, capped by `FAVORITES_EMAIL_DAILY_LIMIT` per day).

Mail goes out through `config/mail.php`: `log` (nothing leaves the server, the
message is written to `storage/logs/mail.log`), `mail` (PHP `mail()`) or `smtp`.
For `info@lufly.tr` set these in `.env` (see `.env.example`):

```dotenv
MAIL_TRANSPORT=smtp
MAIL_HOST=mail.lufly.tr        # your mail server
MAIL_PORT=587                  # 587 with tls, 465 with ssl
MAIL_USERNAME=info@lufly.tr
MAIL_PASSWORD=                 # mailbox password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=info@lufly.tr
MAIL_FROM_NAME="LUFLY"
MAIL_ADMIN_ADDRESS=info@lufly.tr

FEATURE_FAVORITES=true
FAVORITES_NOTIFY_ADMIN=true
FAVORITES_MAX_ITEMS=60
FAVORITES_EMAIL_COOLDOWN=60
FAVORITES_EMAIL_DAILY_LIMIT=5
```

An existing database needs one thing: the `favorites` and `favorite_items`
tables. Either run `php cli migrate`, import the shipped `lufly-database.sql`,
or — on a live MySQL database you would rather not re-import — paste
`database/sql/2026_01_01_000018_favorites_mysql.sql` into phpMyAdmin (it also
records the migration, so a later `php cli migrate` stays quiet).

## AI (Google Gemini)

The AI features run on a pool of free Google AI Studio accounts: every key is a
separate project with its own daily quota, and the client rotates them (a 429 on
one key moves the request to the next). Keys are sent in the `x-goog-api-key`
header — the format AI Studio issues today (`AQ.…`) only authenticates that way.

```dotenv
FEATURE_AI=true
AI_KEY_1=AQ.…        # key of the first Google account
AI_KEY_2=            # second account, and so on up to AI_KEY_5
AI_MODEL=gemini-2.5-flash
AI_MODEL_2=gemini-2.5-flash-lite   # optional, per account
AI_IMAGE_MODEL=gemini-2.5-flash-image   # Nano Banana: draws the planned room
```

Check the pool from the terminal — one tiny request per key, plus an image:

```bash
php cli ai:doctor --image
```

The doctor names the `.env` line when a value could never be a model name
(a comment pasted onto the same line, a line that broke in two), so a 400 from
Google never has to be traced by hand. An image request that answers `429`
while the text keys pass means the account simply has no picture quota — set
`AI_IMAGE_MODEL=` (empty) and the planner hides its picture button.

Every answer is cached (`ai_cache`) and counted (`ai_usage`), so a repeated
question costs nothing and a visitor cannot burn the quota. The `ai_cache` and
`ai_usage` tables come with migration 19; on a live MySQL database paste
`database/sql/2026_01_01_000019_ai_mysql.sql` instead of re-importing the dump.

## Bathroom planner

`/{locale}/planner` (tr: `banyo-planlayici`, cs: `planovac-koupelny`) — the visitor
answers three questions (room size, shower or bathtub, look) and gets a plan:

- **the items and their sizes** come from `config/planner.php`, which also knows
  how much floor each piece needs;
- **the drawing** is a scaled SVG built from those numbers — exact, printable and
  free: no model and no image is involved;
- **the matching products** are found with the ordinary catalogue search (LIKE
  over names, SKUs and search keywords), never by sending the catalog to the AI;
- **the welcome sentence** is the only thing the assistant writes, from a prompt
  of a few hundred tokens that contains the answers and the item sizes — nothing
  more. It is cached, so the second visitor of the same room costs no tokens;
- **the picture of the finished room** is optional, one tap, and the only image
  generation in the flow (`gemini-2.5-flash-image`). While the free image quota
  cannot answer, `PLANNER_RENDER_SOON=true` (the default) shows it as *coming
  soon* instead of offering a button that would only fail;
- **the chat floats on every page** — a bubble in the corner opens the same
  conversation in a panel that can be expanded or dragged to any size, and the
  visitor's size is remembered. On `/planner` the chat is the page, so the
  bubble stays away;
- **"does this piece fit my plan?"** — on a product page the plan offers one
  extra question, answered from **the product's own words** (name, description,
  category) compared with the plan. The catalogue is never swept and no image is
  ever sent: the verdict is computed in PHP and the assistant only rephrases it,
  once, cached per product and room.

If the assistant is off, slow, out of quota, or has no network, the plan, the
drawing and the items still arrive — in every language. Everything is cached in
`ai_cache` and counted in `ai_usage` (one row per call, with the token counts),
and a failed answer is only remembered for five minutes.

```dotenv
FEATURE_PLANNER=true
PLANNER_AI=true                 # the assistant writes the welcome sentence
PLANNER_CHAT=true               # the floating chat on every page
PLANNER_FIT=true                # "does this piece fit my plan?" (text only)
FEATURE_PLANNER_RENDER=true     # the optional picture (uses AI_IMAGE_MODEL)
PLANNER_RENDER_SOON=true        # announce the picture as "coming soon"
PLANNER_RENDER_DAILY=3          # pictures per visitor per day
PLANNER_HANDOFF_EMAIL=info@lufly.tr
PLANNER_WHATSAPP=908503040817
```

The plan ends with a hand-off: one tap sends the whole plan (room, items, sizes,
clearance) to the LUFLY team on WhatsApp, or an e-mail the shop sends with the
visitor in `Reply-To`. Nothing extra to migrate — the planner uses the AI tables.

## CLI (no artisan — it's `php cli …`)

| Command | What it does |
|---|---|
| `php cli serve` | Dev server (like `php -S … server.php`) |
| `php cli migrate` / `rollback` | Run / revert migrations |
| `php cli seed` | Seed the database (idempotent) |
| `php cli db:export-mysql` | Regenerate `lufly-database.sql` from the current database |
| `php cli backup:db` | Dump a backup into `storage/backups/` |
| `php cli schema:dump` | Regenerate `database/schema/schema.sql` |
| `php cli erd` | Regenerate the ER diagram |
| `php cli key:generate` | New `APP_KEY` |
| `php cli docs:api` | Regenerate API documentation from routes |

## Documentation

| File | Contents |
|---|---|
| [docs/Architecture.md](docs/Architecture.md) | The framework: routing, views, modules, request lifecycle |
| [docs/Database.md](docs/Database.md) | SQLite⇄MySQL workflow, schema, migrations, seeders, export |
| [docs/Catalog-Data.md](docs/Catalog-Data.md) | Products, translations, images, SEO — how the catalog data fits together |
| [docs/Localization.md](docs/Localization.md) | en/tr/cs, localized routes, adding a language |
| [docs/Deployment.md](docs/Deployment.md) | XAMPP, shared hosting, production checklist |

## Sanity checks after any change

```bash
# Rebuild dev DB from scratch (sqlite):
php cli migrate && php cli seed

# Refresh the MySQL export so the two artifacts stay in sync:
php cli db:export-mysql
```
