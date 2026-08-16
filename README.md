# Real Estate App — SDC

**Sunview Development and Consultancy (SDC)** — PHP + MySQL property management platform.

Visual reference lives in `../references/` (**read-only**). Do not modify Stitch assets.

Hard fidelity rules: see [`docs/UI_FIDELITY.md`](docs/UI_FIDELITY.md).  
**Deploy:** see [`DEPLOY.md`](DEPLOY.md) · routes: [`docs/ROUTES.md`](docs/ROUTES.md) · QA: [`docs/PHASE7_QA.md`](docs/PHASE7_QA.md).

**Phases 1–8 complete** (foundation → auth → CRUD → public → inquiries → Stitch UI → hardening → deploy readiness).

## Requirements

- PHP 8.1+ (`pdo_mysql`, `fileinfo`, `gd` recommended)
- MySQL 8+ (or MariaDB 10.4+)
- Composer

## Setup (local)

1. Copy `config.local.php.example` → `config.local.php` (local env, DB, `mail.driver=log`).  
   For production, use `config.local.production.php.example` instead — see [`DEPLOY.md`](DEPLOY.md).
2. Import the database:
   ```bash
   mysql -u root -p < database/schema.sql
   mysql -u root -p < database/seed.sql
   ```
3. Install dependencies:
   ```bash
   composer install
   ```
4. Point the web server document root at this folder.
5. Checks:
   ```bash
   php bin/smoke.php
   php health.php
   ```

## Security notes

- Secrets only in `config.local.php` (gitignored).
- **No admin password is seeded.** First visit: `admin/setup.php`, then `admin/login.php`.
- Logout is POST + CSRF. Login and inquiries are rate-limited.
- Uploads under `uploads/properties/` with script execution blocked.
- Root `.htaccess` / Nginx examples deny `database/`, `storage/`, `vendor/`, `bin/`.
- `references/` must never be modified by this application.

## Admin

- `admin/setup.php` — one-time admin creation  
- `admin/login.php` / `admin/logout.php`  
- `admin/index.php` — dashboard  
- `admin/properties.php` / `property-form.php` / `property-archive.php`  
- `admin/inquiries.php` — leads + email reply  

## Public UI

Tailwind CDN + reference tokens via `includes/stitch-head.php` (accepted for v1). Optional compile/self-host later — see `DEPLOY.md` §10.

### Mail

- Local default: `log` → `storage/logs/mail.log`
- Production: `mail.driver=smtp` + Brevo/SMTP in `config.local.php`
- Admin notifications → `mail.admin_notify_email`
