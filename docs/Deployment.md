# Deployment

## XAMPP / local development

1. Clone under `C:\xampp\htdocs\lufly_1`.
2. **Import the database**: phpMyAdmin → Import → `lufly-database.sql` → Go
   (creates database `lufly`, all 40 tables, all data).
3. Copy `.env.example` → `.env` and set:
   ```dotenv
   DB_CONNECTION=mysql
   DB_DATABASE=lufly
   DB_USERNAME=root
   DB_PASSWORD=
   APP_URL=http://localhost/lufly_1/
   ```
4. Open `http://localhost/lufly_1/`.
   Zero-config alternative: `DB_CONNECTION=sqlite` (uses `database/lufly.sqlite`, same content).

`.htaccess` at the project root already rewrites requests to `public/index.php`,
so the public web root can be the project folder itself — nothing else to configure in Apache/XAMPP.

## Shared hosting (cPanel-style, FTP upload)

1. Upload the repository contents to the hosting folder (e.g. `public_html/`).
2. Import `lufly-database.sql` through the hosting's phpMyAdmin.
3. Create `.env` with the hosting's MySQL credentials (`DB_HOST` is usually `localhost`).
4. Production values:
   ```dotenv
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://your-domain.com/
   ```
5. If hosting allows setting the docroot to a subfolder, point it at `public/` —
   otherwise the root `.htaccess` handles routing automatically.
6. Make `storage/` (logs, cache, uploads, backups) writable by PHP.

## Mail (saved-list e-mails, `info@lufly.tr`)

The saved-products list and every form notification leave the store through
`config/mail.php`. Until the mailbox is configured the default transport is
`log`, which writes the complete MIME message to `storage/logs/mail.log` and
sends nothing — handy to test the flow before touching DNS.

```dotenv
MAIL_TRANSPORT=smtp
MAIL_HOST=mail.lufly.tr     # the host your provider gave you for SMTP
MAIL_PORT=587               # 587 = STARTTLS, 465 = implicit TLS
MAIL_USERNAME=info@lufly.tr
MAIL_PASSWORD=              # the mailbox password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=info@lufly.tr
MAIL_FROM_NAME="LUFLY"
MAIL_ADMIN_ADDRESS=info@lufly.tr   # receives a copy of every mailed list
```

`MAIL_TRANSPORT=mail` is the fallback when the host only offers PHP `mail()`
(XAMPP, some cPanel accounts) — the From address then has to be local to the
domain. Test after deploying: open `/{locale}/favorites`, save a product, send
the list and confirm both the visitor copy and the copy in the store inbox.

## Database updates on a live install (no re-import)

Never re-import `lufly-database.sql` over a live database just to add a table —
it drops and recreates everything. Each feature that adds tables ships a
paste-ready MySQL patch next to the migration:

| Patch | Adds |
|---|---|
| `database/sql/2026_01_01_000018_favorites_mysql.sql` | saved products (`favorites`, `favorite_items`) |

phpMyAdmin → select the database → **SQL** tab → paste the file → Go. The patch
is safe to run twice, and it records the migration so a later `php cli migrate`
does not try to create the tables again. With terminal access, `php cli migrate`
does exactly the same.

## Production checklist

- [ ] Import ran with **zero errors** (`#1059`-style identifier issues are fixed in the export)
- [ ] `.env` has `APP_DEBUG=false` on production
- [ ] Rotate the seeded admin password (`/admin`) — bcrypt cost is set via `SECURITY_BCRYPT_COST`
- [ ] `APP_KEY` set (`php cli key:generate` when rotating)
- [ ] `storage/` not publicly listable; `Options -Indexes` is already in `.htaccess`
- [ ] Site reachable in all locales: `/en`, `/tr`, `/cs`
- [ ] robots.txt + sitemap.xml served from `public/`
- [ ] Backups: `php cli backup:db` → `storage/backups/`
- [ ] `MAIL_TRANSPORT=smtp` + credentials set, and one saved list mailed end to end

## Troubleshooting

| Symptom | Cause → Fix |
|---|---|
| 500 / blank page right after deploy | `APP_DEBUG=true` temporarily → `storage/logs/` shows the error |
| `View not found: .../components/X.php` | A deleted component is still referenced — see "View engine" in Architecture.md |
| Login page OK but `/admin` redirects out | Session/cookie on MySQL — confirm `SESSION_LIFETIME` + writable `storage/` |
| `Unknown database 'lufly'` | Import step skipped or `DB_DATABASE` mismatch |
| Mojibake (Türkçe/čeština broken) | Database not utf8mb4 — re-import with the shipped export (it sets charset) |
