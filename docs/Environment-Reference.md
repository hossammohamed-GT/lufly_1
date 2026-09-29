# Environment variable reference

Copy `.env.example` to `.env`; never commit `.env`. Production must use a unique `APP_KEY` generated with `php cli key:generate`.

## Production-critical variables

| Variable | Required | Secret | Production guidance |
|---|---:|---:|---|
| `APP_ENV` | yes | no | `production` |
| `APP_KEY` | yes | yes | Unique 32-byte generated key; application fails closed if missing |
| `APP_DEBUG` | yes | no | `false` |
| `APP_URL` | yes | no | Canonical HTTPS origin, no alternate host |
| `DB_CONNECTION` | yes | no | `mysql` recommended |
| `DB_HOST/PORT/DATABASE/USERNAME` | MySQL | partly | Least-privilege application account |
| `DB_PASSWORD` | MySQL | yes | Secret manager/hosting secret field |
| `SESSION_SECURE_COOKIE` | yes | no | `true` on HTTPS production |
| `SESSION_SAMESITE` | yes | no | `Lax` unless a reviewed integration needs otherwise |
| `MAIL_PASSWORD` | SMTP | yes | Never place in source control |
| `AI_KEY_1..5` | AI | yes | Separate provider keys; rotate after exposure |
| `SECURITY_CSP_REPORT_ONLY` | yes | no | Start `true`, inspect reports, then enforce with `false` |
| `SECURITY_HSTS` | yes | no | Enable only after all host/subdomain traffic is HTTPS |
| `SEO_ENFORCE_HOST` | yes | no | `true` after proxy/HTTPS verification |
| `LOG_LEVEL` | yes | no | `warning` or `info`; never `debug` long-term |
| `DB_LOG` | yes | no | `false` in production |

All mail, AI, assistant, planner, favorites, and quote-box variables are documented inline in `.env.example`. Empty optional integration IDs disable their integrations.

## Secret handling

- Supply secrets through the hosting platform or an untracked `.env` readable only by the service account.
- Rotate `APP_KEY`, database, SMTP and AI credentials after suspected disclosure.
- Do not upload `.env`, SQL backups, logs, or SQLite files beneath the public document root.
- Deploy with `public/` as the web server document root.
