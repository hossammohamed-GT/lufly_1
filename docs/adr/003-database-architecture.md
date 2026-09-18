# ADR 003 - Database: Schema Definitions + Custom Migrations + ORM-lite

## Context

The platform needs repeatable schema evolution on XAMPP MySQL with rollback support,
consistent column conventions, and zero external tooling.

## Problem

Plain SQL dumps give no history or rollback; hand-written DDL per migration drifts
from an evolving "source of truth"; a full ORM is out of scope for native PHP.

## Decision

1. **Schema definitions** (`database/schema/*Schema.php`, Prisma-inspired) are the
   single declarative source of table structure, built from reusable types
   (`id`, `uuid`, `string_50…255`, `email`, `phone`, `slug`, `json`, `long_text`,
   `status`, `timestamps`, `soft_delete`, `seo`).
2. **Custom migration system** - `php cli migrate | rollback | seed`, history in the
   `migrations` table, batched rollback, `up()/down()` per migration executing schema
   definitions through a driver-aware `SchemaBuilder` (MySQL + SQLite).
3. **ORM-lite** - `Model` (active-record base: fillable, casts, timestamps, soft
   delete) over a bound-parameter `QueryBuilder`; repositories wrap models.

`php cli schema:dump` and `php cli erd` derive DDL/ERD from the same definitions.

## Alternatives

1. **Doctrine/Propel** - rejected (dependency constraint).
2. **Pure PDO in controllers** - rejected: duplication, no consistency guarantees.
3. **Declarative SQL diffing (auto-migrations)** - rejected: unsafe implicit drops.

## Consequences

- One source of truth; migrations stay trivial and reviewable.
- Driver abstraction must be maintained when adding column types.
- No relation graph / lazy loading - services compose data explicitly (acceptable at
  this scale, documented).
