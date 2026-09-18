# Security

## Authentication

Session-based (`Core\Auth\Auth` + `SessionGuard` semantics):

- `password_hash` / `password_verify` with bcrypt cost from `security.bcrypt_cost`.
- Session regeneration on login/logout; HttpOnly + SameSite=Lax cookies.
- **Lockout**: after `SECURITY_LOGIN_MAX_ATTEMPTS` (default 5) failures the email is
  locked for `SECURITY_LOGIN_LOCKOUT_SECONDS` (default 300). Every failure is written
  to `security.log`.

## Authorization (RBAC)

Tables: `users`, `roles`, `permissions`, `role_permissions`, `user_roles`.

- Route protection: `->middleware('permission:products.manage')`.
- Programmatic checks: `auth()->userCan('users.manage')`.
- Seeded roles: `admin` (all permissions), `editor`, `viewer`.
- Permission management UI: `/admin/roles` (permission `permissions.manage`).

## CSRF

`CsrfGuard` (web group) validates `_token` (form) or `X-CSRF-Token` (header) on all
unsafe methods. Mismatch → HTTP 419 `csrf_token_invalid`.

```php
<?= csrf_field() ?>   // inside every POST form
```

## Security headers

`SecurityHeaders` middleware applies (configurable in `config/security.php`):
`X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`,
`Referrer-Policy`, `X-XSS-Protection`.

## Upload security

`UploadService` enforces: allowed extension whitelist (images/documents/videos),
blocked executable extensions (`php`, `sh`, `exe`, …), max size
(`UPLOAD_MAX_SIZE` KB), random filenames, storage under `storage/uploads/`.

## SQL injection

All queries go through PDO prepared statements (`Connection::query`). The
QueryBuilder binds every value.

## XSS

Views escape via `e()` (htmlspecialchars ENT_QUOTES). Templates render raw content
only when explicitly intended.

## Exception handling

`Core\Exceptions\Handler` catches everything: typed exceptions map to translated
HTTP responses; 5xx details are hidden unless `APP_DEBUG=true`; every error lands in
`error.log`.

## Audit trail

`audits` table stores before/after JSON per mutation with user + timestamp -
enabled per repository (`$auditing = true`), always on for settings changes.

## Security logging

Failed logins, lockouts, permission denials, login/logout and custom events →
`storage/logs/security.log` (`security` channel).
