# Database

## Two artifacts, always in sync

| File | Role | Used when |
|---|---|---|
| `database/lufly.sqlite` | SQLite dev database | `DB_CONNECTION=sqlite` (zero-config local dev) |
| `lufly-database.sql` | **Self-contained MySQL export** | `DB_CONNECTION=mysql` (XAMPP, production) |

The SQL file creates everything from scratch: `CREATE DATABASE IF NOT EXISTS lufly`,
`USE lufly`, `DROP TABLE IF EXISTS` + `CREATE TABLE` for all 38 tables, then row-per-row `INSERT`s.
Safe to re-import any number of times (phpMyAdmin → Import).

**After changing data** (via admin panel or seeders), regenerate the export so both artifacts stay equal:

```bash
php cli db:export-mysql
```

## Workflow: schema → migrations → seeders

1. **Table definitions** — `database/schema/*.php` (`Blueprint` DSL) are the source of truth for DDL.
   They're compiled per-driver (MySQL / SQLite) by the connection layer.
2. **Migrations** — `database/migrations/2026_01_01_*.php` (17 files) build the tables.
   Run state is tracked in the `migrations` table; the export marks all 17 as applied,
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

## The 38 tables by domain

| Domain | Tables |
|---|---|
| System | `migrations`, `settings`, `activity_logs`, `audits`, `api_logs`, `notifications` |
| Auth | `users`, `roles`, `permissions`, `role_permissions`, `user_roles` |
| I18n | `languages` (+ `*_translations` tables below) |
| Catalog | `products`, `product_translations`, `categories`, `category_translations`, `brands`, `collections`, `collection_translations` |
| Catalog extras | `product_variants`, `product_media`, `product_relations`, `product_dimensions`, `product_documents`, `product_specifications`, `product_search_keywords`, `product_import_logs` |
| Attributes | `attributes`, `attribute_options`, `product_attribute_values` |
| Media | `media` (files on disk, paths under `/images/…`) |
| CMS (schema ready) | `pages`, `page_translations`, `blogs`, `blog_translations` — currently unused/empty |
| SEO | `seo_meta` per `(product_id, locale)` |

## Conventions that matter

- **Translations pattern** — every translatable entity has a sibling `<entity>_translations`
  table keyed `(…_id, locale)` with a UNIQUE constraint. Query joins pick exactly one locale.
- **MySQL identifier limit** — index/constraint names are capped at **64 chars**;
  `Blueprint::indexName()` truncates + appends a stable hash automatically.
- **utf8mb4 everywhere** — the export sets `CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`;
  required for the Turkish and Czech texts.
- **Foreign keys cascade** — deleting a product removes its translations, media links,
  SEO rows etc. (`ON DELETE CASCADE`), so there are no orphans.
- Index **names in the SQLite file can differ** from the MySQL export (SQLite has no length
  limit) — structure and data are identical, only internal index names may be longer.
