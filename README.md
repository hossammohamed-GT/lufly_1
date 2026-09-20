# LUFLY Platform

Website + admin panel for **LUFLY** — a sanitary-ware brand (mixers, WCs, washbasins, shower systems, accessories).
Trilingual public catalog (**English / Türkçe / Čeština**) with a full content admin panel.

Built on a **custom, dependency-free PHP framework** (no Composer, no Laravel) so it can be uploaded to any
classic shared hosting / XAMPP and run immediately.

---

## Quick start (XAMPP / local)

1. **Import the database** — `lufly-database.sql` (repo root). phpMyAdmin → **Import** → choose the file → **Go**.
   It creates the `lufly` database, all 38 tables and all data from scratch.
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
database/migrations/       Migration files (17, all applied)
database/seeders/          Seeders (products/categories/users/roles/settings…)

core/                      The mini-framework (Router, View, PDO layer, Console, Container…)
app/Http/Controllers/      App-level controllers (Home, Contact…)
modules/<Module>/          Self-contained modules:
                           Controllers / Models / Repositories / Services /
                           Requests / Routes / Views   (10 modules)
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
