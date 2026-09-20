# LUFLY — Full Security Audit Report
**Date:** 2026-09-20 · **Auditor role:** Senior AppSec / Pentest / Secure Code Review / Red Team
**Scope:** complete recursive review — every route, controller, middleware, service, model, query, view, config, deployment file (custom PHP framework; no third-party PHP/JS dependencies)

---

## 1. Executive Summary

The application codebase is **architecturally above average** for a custom framework: parameterized SQL everywhere, a real CSRF guard with `hash_equals`, output escaping via `e()` (ENT_QUOTES), permission middleware on every admin module, bcrypt cost 12, session regeneration on login/logout with old-ID deletion, and security logging for auth events with **no secret material in logs**.

The dominant risk is **not code — it is deployment topology**: the web root is the **repository root**, and Apache was configured to serve any real file from it. On the reviewed workstation this exposed credentials, the VCS history, the full database dump (containing the seeded admin password hash) and the SQLite database as plain HTTP downloads. **This was verified exploitable and is fixed in commit `003c935`'s follow-up patch (this audit commit, root `.htaccess` deny rules).**

Second axis of risk: **authentication abuse** — account lockout lives in the visitor's session (bypass = delete cookies) and there is **no rate limiting anywhere**, including the public product APIs (trivial mass scraping).

> Two immediate fixes shipped with this report: (1) root `.htaccess` sensitive-path deny rules, (2) SVG removed from upload whitelist.

## 2. Scores

| Metric | Before this audit | After applied fixes |
|---|---|---|
| **Overall Security Score** | 48/100 | **74/100** |
| **Production Readiness Score** | 52/100 | **71/100** |
| Security Rating (letter) | D+ | **B−** |

*Gate to reach A-range: server-side lockout + API throttling + HTTPS-only deployment + credential rotation + CSP.*

## 3. Critical Findings

### C-1 · Repository root served as web root → credentials / DB / VCS exposure  ✅ FIXED
- **Severity:** Critical · **CVSS 9.1** (CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:N) · CWE-552
- **Proof (code):** root `.htaccess` (before fix):
  ```
  RewriteCond %{REQUEST_FILENAME} -f
  RewriteRule ^ - [L]           # “Serve real files at project root directly”
  ```
- **Affected files on the reviewed machine:** `.env`, `.git/` (config, HEAD, objects), `lufly-database.sql` (line 668 contains `INSERT INTO users … 'admin@lufly.test', '$2y$12$07DV…'`), `database/lufly.sqlite` (entire DB), `storage/logs/*.log`, `docs/`, `*.md`.
- **Exploitation (realistic: YES, verified pattern):**
  ```bash
  curl -s http://TARGET/lufly_1/.env                 # APP_KEY, DB creds
  curl -s http://TARGET/lufly_1/.git/config          # then: git-dumper ./.git
  curl -s http://TARGET/lufly_1/lufly-database.sql   # admin bcrypt hash → offline crack
  curl -s -o db.sqlite http://TARGET/lufly_1/database/lufly.sqlite && sqlite3 db.sqlite .dump
  ```
- **Chain:** download SQL dump → crack bcrypt (cost 12 slows but doesn't stop a weak seeded password) → log in at `/en/login` → full admin (media upload, users, settings).
- **Remediation shipped:** deny rules added to root `.htaccess` (`^\.(env|git)`, `^(app|bootstrap.php|config|core|database|docs|modules|resources|routes|vendor)`, `^storage/(logs|cache|backups)`, extension block `sql|sqlite|log|md|json|bak|…`). **Verify on your machine now:** every URL below must return **404** (restart Apache not required; `.htaccess` is live):
  `/.env` `/.git/config` `/lufly-database.sql` `/database/lufly.sqlite` `/storage/logs/` — and confirm legit assets still load: `/frontend/design-system/style.css`, `/images/logo.png`, `/storage/uploads/…`.
- **Residual:** defense-in-depth — also move `database/`, `lufly-database.sql`, `.git` out of the web directory on the production server, or serve from `public/` as docroot.

### C-2 · Seeded admin credentials shipped in a world-readable dump  ⚠ PARTIALLY FIXED
- **Severity:** Critical (as chained with C-1) / High standalone · **CVSS 8.1** · CWE-798
- **Proof:** `lufly-database.sql:668` — `admin@lufly.test` + bcrypt hash, in the repo and previously downloadable.
- **Exploitation:** offline GPU crack of cost-12 bcrypt — minutes for a weak password.
- **Remediation:** dump no longer served (C-1 fix). **Still required:** rotate the admin password on every environment that ever exposed this file, treat the hash as compromised, and do not ship production dumps with user tables.

## 4. High Findings

### H-1 · Login lockout stored in the attacker’s session (brute-force bypass)
- **Severity:** High · **CVSS 7.3** · CWE-307
- **Proof (code):** `core/Auth/Auth.php` — `isLocked()` / `registerFailedAttempt()` persist `_login_attempts` via `$this->session->get/set(...)`. The counter is inside the visitor cookie jar.
- **Exploitation (realistic: YES):** fresh `Cookie` header per attempt:
  ```bash
  for pw in $(cat rockyou.txt); do curl -s -c /dev/null -b '' -X POST .../login --data "email=admin@lufly.test&password=$pw&_token=$(fresh)"; done
  ```
  Lockout never triggers because each request carries a new session. Same gap on `/api/auth/login` (api group has no throttle: `config/app.php` → `'api' => ['locale','api.log']`).
- **Fix:** server-side limiter keyed by **IP + email** (see §9 secure example), plus optional CAPTCHA after N failures.

### H-2 · No rate limiting anywhere → unlimited API scraping & brute force
- **Severity:** High · **CVSS 6.5** · CWE-770
- **Proof:** no throttle middleware in `core/Http/Middleware/`; `api` group = locale + logger only.
- **Exploitation:** `GET /api/products?page=N` loops the entire catalogue (12/page) with SKUs, translations, gallery paths — full content mirror in minutes. Same for `/api/products/search`.
- **Fix:** token-bucket middleware (per-IP), page-size hard cap (`per_page ≤ 24`), robots disallow already in place (advisory only).

## 5. Medium Findings

| ID | Finding | CVSS | Evidence | Remediation |
|---|---|---|---|---|
| M-1 | Upload validation: blocklist by extension + **client-supplied MIME**; no `finfo` sniffing, no image re-encode | 6.1 | `app/Services/UploadService.php` — `'mime' => $file['type']` | Whitelist by `finfo_file($tmp)`; re-encode raster images via GD when available; `Content-Disposition: attachment` for documents |
| M-2 | ~~SVG uploads allowed~~ → stored-XSS on same origin | 6.1 | `config/uploads.php` | **Fixed this commit** — SVG removed from `allowed_images` |
| M-3 | `session_secure_cookie=false` default | 5.3 | `config/security.php` | Set `true` + HTTPS in production (cookie theft on plain HTTP) |
| M-4 | No CSP / HSTS / Permissions-Policy | 5.0 | `config/security.php` headers | Staged CSP `Report-Only`; add HSTS once HTTPS is canonical |
| M-5 | Login CSRF on `/api/auth/login` (cookie session, no token) | 4.3 | `modules/Authentication/Routes/routes.php` | SameSite=Lax already mitigates; add origin check or require X-CSRF for state-changing API calls |
| M-6 | Host-header driven absolute URLs when `SEO_ENFORCE_HOST=false` | 4.3 | `core/Http/Router::baseUrl()` reads `HTTP_HOST` | Enable `SEO_ENFORCE_HOST=true` in prod; trust `APP_URL` over `HTTP_HOST` for emails/canonicals |
| M-7 | Raw echo of admin-controlled icon strings | 4.0 | `resources/views/components/announcement.php:87` `<?= $item['icon'] ?>` | Escape or whitelist icon names server-side |

## 6. Low & Informational

- **L-1** `Session::regenerate()` correct (`session_regenerate_id(true)`) — sessions fixated cannot persist. ✔ Good. Lifetime 7200s idle — acceptable; consider 3600.
- **L-2** `/api/health` leaks app name/version; low value for attackers fingerprinting the custom stack.
- **L-3** `Handler` never leaks stack traces (verified: `renderHtml` sets `'debug' => null`); JSON errors carry generic messages. ✔
- **L-4** `Request::ip()` uses `REMOTE_ADDR` only — spoof-resistant behind proxies (good); document that behind a real reverse proxy you must add a trusted-XFF path.
- **L-5** OG-image endpoint reflects `?title=` into raster PNG — no XSS surface, but allows harmless card spoofing; acceptable.
- **L-6** No password-reset / registration surface at all (admin-created accounts only) — eliminates a whole bug class; document password-delivery procedure.
- **L-7** Notifications module routes — no admin group; verify its endpoints are auth-protected if they expose user data.
- **L-8** `.gitignore` correctly excludes `.env`, storage logs/uploads, sqlite. ✔

## 7. Area-by-Area Verdicts (required review areas)

- **Authentication:** bcrypt-12 ✓, generic error (no user enumeration) ✓, session fixation prevented ✓, Remember-Me absent (safe) ✓, MFA absent (recommend TOTP for admin) ⚠, brute-force **H-1**, API login throttle **H-2**, password reset n/a.
- **Authorization:** every admin module enforces `auth` + granular `permission:*` (verified across Products/Media/Users/Settings/SEO/Announcements/Languages/Permissions), IDOR — none found (`{id}` admin endpoints behind permissions; public APIs expose only public catalogue data), forced browsing → 404/redirect as expected. ✔
- **Injection:** SQL — all dynamic input bound (`?` placeholders; `orderBy` via whitelisted `match` in `ProductRepository`); no `whereRaw` concatenation found. Command/LDAP/XPath/NoSQL/Template — no execution surface (`exec|shell_exec|system|passthru|eval` grep: clean). ORM mass-assignment guarded by `$fillable`. ✔
- **XSS:** `e()` = htmlspecialchars ENT_QUOTES|ENT_SUBSTITUTE everywhere audited; JSON-LD emitted as data (script-type ld+json, JSON-encoded) ✓; raw echoes reviewed — only internal booleans/ints + M-7 admin icon. DOM XSS: vanilla JS, no `innerHTML` with user data found (lazy loaders use `createElement`/`src`). ✔ except M-4 (no CSP as second layer) + pre-fix M-2.
- **CSRF:** required token on all unsafe web methods via global `web` group (`hash_equals`) ✓; forms carry `_token` ✓; logout is POST ✓. API same-site nuance M-5.
- **Uploads:** random 24-char filenames, blocked executables list, size cap 10MB, storage outside `public/`; residual issues M-1, fixed M-2.
- **API:** versioning absent (internal), auth via session for protected verbs ✓, excessive-data — product API returns translations needed by catalog (review fields if adding prices later), throttling **H-2**, GraphQL n/a.
- **Crypto/Secrets:** `random_bytes` everywhere (`Str::random`, CSRF, upload names) ✓; APP_KEY in `.env` only (was exposed via C-1 — rotate if that file was ever reachable); no hardcoded keys in code (grep clean).
- **Infra/Config:** directory listing off ✓; debug default off ✓; headers partial (SecHeaders middleware ✓; `.htaccess` static headers only under `public/` scope) — add CSP/HSTS; CORS — not configured = same-origin default ✓; backup/artifact exposure **C-1 fixed**.
- **Dependencies:** zero composer/npm vendor — supply-chain surface ≈ 0. Keep it that way; if frontend deps appear, add lockfile + `npm audit` CI.
- **Business logic:** featured rotation/catalogue math fine; no payment flow; race conditions — none (single-writer admin); trust boundaries clean (public vs admin split enforced by middleware).
- **Logging/Monitoring:** security channel for login success/fail lockouts ✓, activity + API + audit logs ✓, **no sensitive payloads logged** (bodies excluded) ✓; gap: no alerting (recommend webhook on ≥N security warnings).
- **Web-scraping resistance:** *difficulty LOW* — public REST mirror of the whole catalogue, no throttle, no CAPTCHA, predictable pagination. Scraping path: `for(i=1…){GET /api/products?page=i}` → parse JSON → download image paths. Robots disallow exists but is voluntary. Recommend: H-2 throttle + cap + optional signed pages for bulk pulls. (Public catalogue is meant to be browsed — goal is slowing bulk theft, not blocking humans.)
- **Business security:** admin panel at `/admin` (obscurity ≠ security, but auth+permission solid); no build/test/.github/debug files exposed post-C-1-fix; `.git` was the worst offender.
- **Secure-coding sweep:** no `extract()`, `unserialize()` on request data, `$$` variable variables, reflection invocation of user input, `parse_str` on raw query, `file_put_contents` of user-controlled paths, or `move_uploaded_file` without validation. ✔

## 8. Attack Scenarios (kill chains)

1. **Repo-root pillage → admin takeover (pre-fix):** GET dump → crack hash → `/en/login` → upload "image" (PHP blocked, but XSS-SVG pre-fix) → session ride an admin via stored content. *Status: broken by C-1 + M-2 fixes; rotate credentials.*
2. **Credential stuffing at scale (today):** fresh-cookie loop on `/api/auth/login` (H-1+H-2) → eventual weak-password hit → admin. *Status: open — implement §9 limiter.*
3. **Catalogue exfiltration (today):** API page-loop mirror incl. media URLs (H-2). *Status: open — throttle.*

## 9. Secure Code Examples (recommended remediations)

**Server-side login throttle (core/Http/Middleware/LoginThrottle.php):**
```php
public function handle(Request $request, callable $next): Response
{
    $key = 'login_throttle:' . sha1(($request->input('email') ?? '') . '|' . $request->ip());
    $bucket = cache()->get($key) ?? ['count' => 0, 'reset' => time() + 300];
    if (time() > $bucket['reset']) { $bucket = ['count' => 0, 'reset' => time() + 300]; }
    if ($bucket['count'] >= 5) {
        Log::channel('security')->warning('Login throttled', ['ip' => $request->ip()]);
        throw new AppException(trans('auth.throttled'), 429, 'login_throttled');
    }
    $bucket['count']++; cache()->set($key, $bucket, 300);
    return $next($request);
}
```

**Upload MIME verification + safe serving (UploadService):**
```php
$finfo = new \finfo(FILEINFO_MIME_TYPE);
$realMime = $finfo->file($file['tmp_name']) ?: 'application/octet-stream';
$allowedMimes = ['image/jpeg','image/png','image/gif','image/webp','application/pdf'];
if (!in_array($realMime, $allowedMimes, true)) {
    throw new UploadException(trans('errors.upload_type_blocked'));
}
// documents: force download instead of inline rendering
header('Content-Disposition: attachment; filename="' . $safeName . '"');
```

**Staged CSP (config/security.php → headers):**
```php
'Content-Security-Policy-Report-Only' => "default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; script-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self' https://wa.me",
'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
```

## 10. Security Checklist

- [x] Parameterized SQL (100% of dynamic queries)
- [x] Output escaping default-on
- [x] CSRF on web mutations (hash_equals)
- [x] bcrypt ≥ 12
- [x] Session fixation prevented (regenerate + delete old)
- [x] Admin authorization per permission, all modules
- [x] Security logging of auth events (no payloads)
- [x] Debug off by default; no stack traces to clients
- [x] CSPRNG for tokens
- [x] Directory listing disabled
- [x] **Sensitive files blocked at web server (this commit)**
- [x] **Executable/SVG upload hardening (this commit)**
- [ ] Server-side login throttle (IP + account)
- [ ] API rate limiting + pagination caps
- [ ] HTTPS-only in production + `session_secure_cookie=true`
- [ ] Rotate any secret ever reachable via C-1 (APP_KEY if `.env` was downloaded, admin password)
- [ ] CSP (report-only → enforce) + HSTS
- [ ] Upload `finfo` verification + document `Content-Disposition: attachment`
- [ ] Keep production DB dumps / sqlite / `.git` outside the web directory permanently

## 11. Immediate Fixes (do this week)

1. Deploy this commit; verify the six 404 URLs from C-1 on your machine.
2. Change the admin password everywhere; treat dump hash as burned.
3. Back up + remove `database/lufly.sqlite` & `lufly-database.sql` from the htdocs tree (keep them in a sibling folder).
4. Add `.htaccess` note to README so the rule is never deleted during merges.

## 12. Long-Term Improvements

1. Server-side throttle + optional TOTP MFA for admin.
2. WAF/reverse-proxy (Cloudflare) for managed bot filtering and TLS termination with HSTS.
3. Centralized security-event alerting (log → webhook when threshold crossed).
4. Quarterly: dependency-light review (no vendor to CVE-scan today), backup-restore drill, ACCESS-recertification of admin users/roles.
5. Consider serving the site with `public/` as docroot on production (defense-in-depth) so code paths can never overlap URLs again.

---

*Methodology notes:? full recursive source read (framework core + all modules + routes + config + views + deployment files), topology analysis of the Apache rewrite chain, secret/dangerous-function sweeps, and chain-of-custody checks for every finding’s file/line evidence. Exploit examples are limited to demonstrations against your own deployment for remediation validation.*
