# Shared Hosting & cPanel Deployment Guide (Pure PHP + MySQL)

This platform is engineered to run on standard **Shared Hosting / cPanel** (Apache + PHP 8.2/8.3 + MySQL) with **zero external dependencies** or Node.js background services.

---

## 1. Prepare MySQL Database on cPanel

1. Log into your **cPanel** account.
2. Open **MySQL® Database Wizard**.
3. Create a new database (e.g. `cpaneluser_lufly`).
4. Create a user (e.g. `cpaneluser_dbuser`) with a strong password.
5. Grant **ALL PRIVILEGES** to the user on this database.

---

## 2. Import Database Schema & Data

1. In cPanel, open **phpMyAdmin**.
2. Select your newly created database (`cpaneluser_lufly`).
3. Click the **Import** tab.
4. Choose the file `database/schema/schema.sql` from this repository and click **Go**.
5. *(Optional)* If you have SSH access, you can run migrations and seeders directly:
   ```bash
   php cli migrate
   php cli seed
   ```

---

## 3. Upload Project Files

### Method A: Upload directly into `public_html` (Standard cPanel)
Upload all project files to `public_html/`. The included root `.htaccess` handles routing automatically:
- Static assets (`frontend/`, `public/images/`, `public/videos/`) are served directly.
- All requests are routed through `public/index.php`.

### Method B: Document Root set to `public_html/public` (Recommended for maximum security)
If your cPanel or VirtualHost allows pointing the document root:
- Place the core code outside web root (e.g. `/home/username/lufly`).
- Place the contents of `public/` inside `public_html/`.
- Update `public/index.php` paths accordingly.

---

## 4. Configure Production Environment (`.env`)

In your cPanel File Manager, edit or create `.env`:

```ini
APP_NAME="LUFLY Platform"
APP_ENV=production
APP_KEY=base64:0HqWdxBFQdH+vydgujjGEac2fa/4w41DvnqkabFviSk=
APP_DEBUG=false
APP_URL=https://lufly.tr
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_TIMEZONE=Europe/Istanbul

# Production MySQL Database
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=cpaneluser_lufly
DB_USERNAME=cpaneluser_dbuser
DB_PASSWORD=your_secure_password_here

LOG_LEVEL=warning
LOG_CHANNEL=app

CACHE_DRIVER=file
SESSION_LIFETIME=7200

MAIL_TRANSPORT=smtp
MAIL_HOST=mail.lufly.tr
MAIL_PORT=465
MAIL_USERNAME=info@lufly.tr
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=info@lufly.tr
MAIL_FROM_NAME="LUFLY Sanitary Ware"

UPLOAD_DISK=local
UPLOAD_MAX_SIZE=10240

SECURITY_BCRYPT_COST=12
SECURITY_LOGIN_MAX_ATTEMPTS=5
SECURITY_LOGIN_LOCKOUT_SECONDS=300

# Feature Flags
FEATURE_B2B_INQUIRY=true
FEATURE_SHOW_PRICES=false
FEATURE_MULTILINGUAL=true
FEATURE_THEME_SWITCHER=true
FEATURE_SPECS_DOWNLOAD=true
FEATURE_QUICK_VIEW=true
```

---

## 5. Storage Directory Permissions

Ensure the `storage/` directory and its subdirectories are writable by the web server:
```bash
chmod -R 775 storage/
# Or via cPanel File Manager: Permissions -> 755 / 775 on storage/
```

---

## 6. Verification Checklist

- [x] Homepage loads securely over HTTPS: `https://lufly.tr/en`
- [x] Arabic RTL version loads: `https://lufly.tr/ar`
- [x] Turkish version loads: `https://lufly.tr/tr`
- [x] Products catalog with 283 items and images: `https://lufly.tr/en/products`
- [x] Direct WhatsApp quotation inquiries open `+90 850 3040 817`
- [x] Admin panel accessible at `/admin` (Default: `admin@lufly.test` / `password`)
