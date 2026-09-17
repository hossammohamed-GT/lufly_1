# Lufly Platform — Enterprise Native PHP Foundation

A production-ready, enterprise-level platform written in **pure PHP 8.2+** — no
Laravel, Symfony or CodeIgniter. Designed to run on **XAMPP** (Apache + PHP + MySQL)
and to serve as the starting point for a large multilingual company website, admin
panel, CRM and future business systems.

## Highlights

- **Internal framework** (`core/`): DI container, router (groups, params, middleware,
  localized URLs), request/response, middleware pipeline, ORM-lite, schema builder,
  migration system, validator, view engine, exception handler, console.
- **Modular architecture** (`modules/`): Authentication, Users, Permissions,
  Languages, Products, Media, Settings, SEO, Notifications — each with Controllers /
  Services / Repositories / Models / Routes / Views / Requests.
- **Prisma-inspired database layer** (`database/schema` + `migrations` + `erd`),
  reusable data types (`id`, `uuid`, `string_50…255`, `email`, `phone`, `slug`,
  `json`, `long_text`, `status`, `timestamps`, `soft_delete`, `seo`).
- **Localization without limits**: static translations (`resources/lang`), dynamic
  `*_translations` tables and translated routes (`/en/products`, `/tr/urunler`,
  `/cs/produkty`). English, Turkish and Czech included.
- **Design system** (`frontend/design-system/`): token-based CSS with light/dark
  themes, component library (Button, Input, Card, Modal, Navbar, Footer,
  ThemeSwitcher, LanguageSwitcher).
- **Security**: sessions, bcrypt, CSRF, RBAC (roles/permissions), lockouts, security
  headers, upload whitelist, audit trail, translated error pages.
- **Logging**: file channels (`app`, `error`, `database`, `api`, `security`, `mail`)
  + activity logs, audit logs, API logs in the database.
- **Standardized API** with success/error/pagination envelopes and
  auto-generated endpoint docs (`php cli docs:api`).

## Quick start (Local Development)

After cloning, run the built-in server from the project root. The router file is
required so application routes and files under `public/` are served together:

```bash
php cli serve --host=127.0.0.1 --port=8080
```

Then open `http://127.0.0.1:8080/en/`.

Do not run `php -S 127.0.0.1:8080` without `server.php`; that can load the page
without serving the public frontend assets correctly.

## Quick start (XAMPP)

```bash
# 1. place the project in htdocs, e.g. C:\xampp\htdocs\lufly
cp .env.example .env          # set DB_DATABASE / DB_USERNAME / DB_PASSWORD / APP_URL
php cli key:generate

# 2. create the database
mysql -u root -e "CREATE DATABASE lufly CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 3. build the schema + demo data
php cli migrate
php cli seed

# 4. open http://localhost/lufly
```

| Account | Password | Role |
| --- | --- | --- |
| `admin@lufly.test` | `password` | admin |

Full instructions: [`docs/Deployment.md`](docs/Deployment.md).

## CLI

```
php cli migrate [--fresh]     run migrations
php cli rollback [--steps=N]  roll back the last batch(es)
php cli seed [--class=Name]   run seeders
php cli schema:dump           DDL dump from schema definitions
php cli erd                   Mermaid ER diagram
php cli docs:api              regenerate docs/API-Endpoints.md
php cli serve [--port=8080]   PHP built-in dev server
php cli key:generate          write APP_KEY to .env
```

## Project structure

```
app/        providers, base controller, shared services & models
core/       internal framework (container, http, db, validation, logging, ...)
modules/    feature modules (Products, Users, Languages, Media, Settings, SEO, ...)
config/     app, database, logging, localization, security, uploads, mail, cache, seo
database/   migrations/ seeders/ schema/ erd/
resources/  lang/{en,tr,cs}/ + views/ (layouts, components, errors)
frontend/   design system tokens + components + JS
admin/      admin panel assets
routes/     web.php, admin.php, api.php
storage/    logs/ cache/ uploads/
docs/       architecture docs + ADRs
```

## Documentation

| Document | Topic |
| --- | --- |
| [`docs/Architecture.md`](docs/Architecture.md) | layers, lifecycle, DI, modules |
| [`docs/Database.md`](docs/Database.md) | migrations, schema system, types, soft delete |
| [`docs/Localization.md`](docs/Localization.md) | static + dynamic translations, localized routing |
| [`docs/Design-System.md`](docs/Design-System.md) | tokens, themes, components |
| [`docs/Logging.md`](docs/Logging.md) | channels, activity, audit, security logs |
| [`docs/Services.md`](docs/Services.md) | service layer catalogue |
| [`docs/API.md`](docs/API.md) | envelopes, endpoints, error codes |
| [`docs/Security.md`](docs/Security.md) | auth, RBAC, CSRF, uploads, audit |
| [`docs/Deployment.md`](docs/Deployment.md) | XAMPP setup + production checklist |
| [`docs/adr/`](docs/adr/) | architecture decision records |

The legacy visual atlas of this repository is preserved at
[`frontend/design-system/atlas.html`](frontend/design-system/atlas.html).

.
