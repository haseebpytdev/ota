# Staging Deployment Guide (`ota.haseebasif.com`)

This guide prepares Laravel OTA for controlled staging deployment.

## Server Requirements

- PHP `8.2+` (recommended `8.3`)
- Composer `2.x`
- Node.js `20+` and npm
- Web server: Nginx or Apache
- Database: MySQL/MariaDB (or equivalent production-grade DB)

Required PHP extensions:

- `bcmath`
- `ctype`
- `curl`
- `dom`
- `fileinfo`
- `json`
- `mbstring`
- `openssl`
- `pdo`
- `pdo_mysql`
- `session`
- `tokenizer`
- `xml`

## Environment (`.env`) Requirements

Use safe staging/production values:

- `APP_NAME="Asif Travels"`
- `APP_ENV=staging` (or `production`)
- `APP_DEBUG=false`
- `APP_URL=https://ota.haseebasif.com`
- `OTA_DEFAULT_AGENCY_SLUG=asif-travels`

Duffel / supplier:

- `DUFFEL_DEFAULT_BASE_URL=https://api.duffel.com`
- `DUFFEL_API_VERSION=v2`
- Set supplier credentials only in admin or secure env, never in repo.

FX:

- `FX_RATE_ENDPOINT=https://api.frankfurter.app/latest`
- `FX_RATE_TIMEOUT_SECONDS=5`
- `FX_RATE_CACHE_TTL_SECONDS=900`

Queue / cache / session / mail:

- `QUEUE_CONNECTION=database`
- `CACHE_STORE=database` (or redis)
- `SESSION_DRIVER=database`
- `MAIL_*` configured for staging relay

## Deployment Commands

Run from project root:

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm install
npm run build
```

If Node/NPM cannot run on the server, build artifacts locally/CI and upload only `public/build` (never upload `node_modules`).

## OTA Data Prep Commands

Use only for staging setup/validation:

```bash
php artisan ota:repair-legacy-data
php artisan ota:prepare-duffel-test --agency=asif-travels
php artisan ota:import-airports-airlines --path=storage/app/imports/kaggle/airports-global
php artisan ota:import-airports-airlines --path=storage/app/imports/kaggle/airline-logos --logos
```

Kaggle imports are offline bootstrap inputs only, and **not used at runtime**.

## Safety Checks Before Go-Live

Run:

```bash
php artisan ota:production-check
```

This validates:

- debug mode disabled
- key/url/database/storage readiness
- default agency/admin presence
- airport/airline reference data
- supplier credential completeness
- markup rule integrity
- custom error pages presence

## Web Server Notes

- Web root must point to `.../public` (never project root).
- Ensure HTTPS for `ota.haseebasif.com` and keep `APP_URL=https://ota.haseebasif.com`.

Nginx baseline:

```nginx
root /path/to/ota/public;
index index.php;
try_files $uri $uri/ /index.php?$query_string;
```

## Permissions

Writable paths:

- `storage/`
- `bootstrap/cache/`

Typical Linux commands:

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## Optional Staging Playwright Smoke

```bash
STAGING_BASE_URL=https://ota.haseebasif.com npm run e2e:desktop
STAGING_BASE_URL=https://ota.haseebasif.com npm run e2e:mobile
```

