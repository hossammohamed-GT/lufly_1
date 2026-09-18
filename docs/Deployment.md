# Deployment (XAMPP)

## Requirements

- PHP **8.2+** (`pdo_mysql` enabled - XAMPP default)
- MySQL 5.7+/MariaDB
- Apache with `mod_rewrite` (XAMPP default)

## Local development with PHP built-in server

From the project root, use the bundled router so both the application and
`frontend/` assets are served correctly:

```bash
php cli serve --host=127.0.0.1 --port=8080
```

Open `http://127.0.0.1:8080/en/` after the server starts.

Do not use `php -S 127.0.0.1:8080` by itself from the project root. Without
`server.php`, the request routing and public asset paths can be incorrect and
the page may appear as unstyled HTML.

## Setup

1. Copy the project into `C:\xampp\htdocs\lufly` (or a Linux htdocs path).
2. Duplicate the environment file and adjust values:

   ```bash
   cp .env.example .env       # DB_DATABASE, DB_USERNAME, DB_PASSWORD, APP_URL
   php cli key:generate       # writes APP_KEY
   ```

   `APP_URL` must match how the browser reaches the app, e.g.
   `http://localhost/lufly`.

3. Import the database. Two equivalent options:

   **Option A - one-file import (recommended).** Import the bundled
   [`lufly-database.sql`](../lufly-database.sql) through phpMyAdmin
   (*Import -> Choose file -> Go*). It creates the `lufly` database, all
   tables (full product architecture + announcements) and all seed data -
   the site is fully usable right after the import.

   From the command line it is the same thing:

   ```bash
   mysql -u root < lufly-database.sql
   ```

   **Option B - build it yourself.** Create the database then run the
   framework migrations and seeders (identical result):

   ```sql
   CREATE DATABASE lufly CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

   ```bash
   php cli migrate
   php cli seed
   ```

   After changing the schema or seeders, regenerate the export so it stays
   the single source of truth for deployment:

   ```bash
   php cli db:export-mysql    # refreshes lufly-database.sql
   ```

5. Ensure writability of `storage/` (logs, cache, uploads).

6. Browse to `http://localhost/lufly` - the bundled root `.htaccess` rewrites all
   non-file requests into `public/index.php`, while real files (frontend assets,
   docs) are served directly.

## Default credentials

| Email | Password | Role |
| --- | --- | --- |
| `admin@lufly.test` | `password` | admin |

**Change or remove this account before going live.**

## Alternative: VirtualHost on public/

```apache
<VirtualHost *:80>
    DocumentRoot "C:/xampp/htdocs/lufly/public"
    ServerName lufly.test
</VirtualHost>
```

Set `APP_URL=http://lufly.test` accordingly.

## Production checklist

- `APP_ENV=production`, `APP_DEBUG=false`
- `APP_KEY` generated, strong DB credentials
- Seed account removed; passwords ≥ 12 chars
- `storage/` outside web reach when docroot is `public/` (already the case);
  with the root `.htaccess` mode, add a `Deny from all` for `storage/`
- HTTPS: set `session_secure_cookie => true` in `config/security.php`
- Backups: DB dump + `storage/uploads`

## Useful commands

```bash
php cli list
php cli migrate --fresh
php cli rollback --steps=1
php cli schema:dump
php cli erd
php cli docs:api
php cli serve --port=8080      # PHP built-in server (no Apache needed)
```
