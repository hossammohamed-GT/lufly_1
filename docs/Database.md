# Database

## Connection

PDO based (`core/Database/Connection.php`), configured in `config/database.php` and
`.env` (`DB_CONNECTION=mysql` by default; `sqlite` available for local tooling).
Every executed statement is written to `storage/logs/database.log`.

## Migrations

```bash
php cli migrate            # run pending migrations
php cli migrate --fresh    # drop all tables, then migrate
php cli rollback           # roll back the last batch
php cli rollback --steps=2
php cli seed               # run DatabaseSeeder
php cli seed --class=ProductSeeder
```

- History is tracked in the `migrations` table (`migration`, `batch`, `applied_at`).
- Each migration implements `up()` and `down()` (`core/Database/Migration/Migration.php`).
- Files live in `database/migrations/` and are applied in filename order.

## Schema definitions (Prisma-inspired)

Declarative table descriptions live in `database/schema/*Schema.php` and extend
`Core\Database\Schema\SchemaDefinition`. Migrations execute them:

```php
public function up(): void
{
    $this->schema->createFromDefinition(new ProductsSchema());
}
```

Generate the full DDL or an ER diagram from the definitions:

```bash
php cli schema:dump   # database/schema/schema.sql
php cli erd           # database/erd/erd.md (Mermaid)
```

## Reusable data types

`Core\Database\Schema\Types` centralizes column shapes, exposed on every Blueprint:

| Type | Meaning |
| --- | --- |
| `id()` | BIGINT unsigned auto-increment PK |
| `uuid(col)` | CHAR(36) |
| `string_50/100/255(col)` | VARCHAR(50/100/255) |
| `email(col)` / `phone(col)` | VARCHAR(255) / VARCHAR(50) |
| `slug(col)` | VARCHAR(255) + index |
| `json(col)` | JSON |
| `long_text(col)` | LONGTEXT |
| `status(col)` | VARCHAR(50) default `active` + index |
| `timestamps()` | created_at + updated_at |
| `soft_delete()` | deleted_at (nullable, indexed) |
| `seo(col)` | nullable JSON meta blob |

## Soft deletes

Models set `protected static bool $softDelete = true;` (default). `Model::delete()`
stamps `deleted_at`; `Model::query()` excludes trashed rows; `Model::withTrashed()`
and `restore()` are available.

## Dynamic translation tables

`product_translations`, `category_translations`, `page_translations`,
`blog_translations` - each keyed by `(entity_id, locale)` with a unique constraint.
Managed through `LocalizationService::syncTranslations()`.

## Audit trail

Repositories with `$auditing = true` write before/after snapshots to `audits`
(entity, entity_id, action, before, after, user_id, timestamp). See `docs/Security.md`.
