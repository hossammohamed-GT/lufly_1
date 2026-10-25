# Database

## Local runtime + one canonical server import

| File | Role | Used when |
|---|---|---|
| `database/lufly.sqlite` | **Complete local runtime database; no import needed** | `DB_CONNECTION=sqlite` (the local default) |
| `lufly-database.sql` | **Complete MySQL schema + all current data** | `DB_CONNECTION=mysql` (shared hosting, production, optional local MySQL) |

These are independent runtime choices for local and server environments. The SQLite file keeps local
setup working, while the SQL file remains the single MySQL installation/reset artifact; no partial SQL
patch copies are shipped.

For MySQL, create an empty database in the hosting panel, select it in phpMyAdmin, then import the file.
It intentionally contains **no `CREATE DATABASE` and no `USE` statement**. It rebuilds all 45
application tables with `DROP TABLE IF EXISTS` + `CREATE TABLE`, followed by row-per-row `INSERT`s.
Use it for fresh installations and deliberate full resets, not as a non-destructive live update.

**After changing data** (via admin panel or seeders), regenerate the export from the active database:

```bash
php cli db:export-mysql
```

The exporter enforces the same single-file behavior, so database-creation statements are not
reintroduced on the next export.

## Workflow: schema → migrations → seeders

1. **Table definitions** — `database/schema/*.php` (`Blueprint` DSL) are the source of truth for DDL.
   They're compiled per-driver (MySQL / SQLite) by the connection layer.
2. **Migrations** — `database/migrations/2026_01_01_*.php` (21 files) build the tables.
   Run state is tracked in the `migrations` table; the export marks all 21 as applied,
   so a freshly imported MySQL database never re-runs them.
   Migrations run **only via CLI** (`php cli migrate`), never at HTTP boot.
3. **Seeders** — `database/seeders/*` populate reference data:
   `LanguageSeeder, RoleSeeder, PermissionSeeder, UserSeeder, SettingSeeder, CategorySeeder,
   ProductSeeder, CatalogSeeder, AnnouncementSeeder`.
   Product/category content comes from `database/seeders/data/products.json` + `categories.json`;
   seeding is idempotent.

```bash
php cli migrate          # build schema
php cli seed             # fill data
php cli backup:db        # → storage/backups/
```

## The 45 tables by domain

| Domain | Tables |
|---|---|
| System | `migrations`, `settings`, `activity_logs`, `audits`, `api_logs`, `notifications` |
| Auth | `users`, `roles`, `permissions`, `role_permissions`, `user_roles` |
| I18n | `languages` (+ `*_translations` tables below) |
| Catalog | `products`, `product_translations`, `categories`, `category_translations`, `brands`, `collections`, `collection_translations` |
| Catalog extras | `product_variants`, `product_media`, `product_relations`, `product_dimensions`, `product_documents`, `product_specifications`, `product_search_keywords`, `product_import_logs` |
| Attributes | `attributes`, `attribute_options`, `product_attribute_values` |
| Media | `media` (files on disk, paths under `/images/…`) |
| CMS & announcements | `pages`, `page_translations`, `blogs`, `blog_translations`, `announcements`, `announcement_translations` |
| SEO | `seo_meta` per `(product_id, locale)` |
| AI & finder | `ai_cache`, `ai_usage`, `assistant_leads` |
| Saved products | `favorites`, `favorite_items` |
| Quotation boxes | `boxes`, `box_items` |

## Conventions that matter

- **Translations pattern** — every translatable entity has a sibling `<entity>_translations`
  table keyed `(…_id, locale)` with a UNIQUE constraint. Query joins pick exactly one locale.
- **MySQL identifier limit** — index/constraint names are capped at **64 chars**;
  `Blueprint::indexName()` truncates + appends a stable hash automatically.
- **utf8mb4 everywhere** — the export sets `CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`;
  required for the Turkish and Czech texts.
- **Foreign keys cascade** — deleting a product removes its translations, media links,
  SEO rows etc. (`ON DELETE CASCADE`), so there are no orphans.
- **Local and server databases are both supported.** `database/lufly.sqlite` is the tracked local
  runtime; `lufly-database.sql` is the only packaged MySQL import. Generated SQLite copies and partial
  SQL patches are ignored, and CI builds an isolated temporary SQLite database from migrations.
