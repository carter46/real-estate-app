# Real Estate App — Phase 1 Foundation (SDC)

**Sunview Development and Consultancy (SDC)** — PHP + MySQL property management platform.

Visual reference lives in `../references/` (**read-only**). Do not modify Stitch assets.

Hard fidelity rules: see [`docs/UI_FIDELITY.md`](docs/UI_FIDELITY.md).

## Requirements

- PHP 8.1+
- MySQL 8+ (or MariaDB 10.4+)
- Composer

## Setup

1. Copy `config.local.php.example` to `config.local.php` and set DB (and later mail) credentials.  
   Local defaults use `mail.driver = log` and `app.debug = true`.
2. Create/import the database:
   ```bash
   mysql -u root -p < database/schema.sql
   mysql -u root -p < database/seed.sql
   ```
3. Install PHP dependencies:
   ```bash
   composer install
   ```
4. Point your web server at this `real-estate-app/` folder.
5. Open `index.php` — it reports config, DB, and PHPMailer status (without leaking raw DB errors).

## Security notes

- Secrets belong only in `config.local.php` (gitignored).
- **No admin password is seeded.** First visit: `admin/setup.php`, then `admin/login.php`.
- Uploads live under `uploads/properties/` with script execution blocked via `.htaccess`.
- `references/` must never be modified by this application.

## Admin (Phase 2–3)

- `admin/setup.php` — one-time admin password creation  
- `admin/login.php` / `admin/logout.php`  
- `admin/index.php` — protected dashboard with live counts  
- `admin/properties.php` / `property-form.php` / `property-archive.php` — property CRUD + gallery  
- Sidebar: Dashboard Overview · Manage Properties · Inquiries · Exit to Site  

## Phase boundary

Contact/inquiry email is Phase 5 (done). Full Stitch pixel polish is Phase 6.

### Mail

- Default local driver: `log` (writes `storage/logs/mail.log`)
- For Brevo/SMTP: set `mail.driver=smtp` and credentials in `config.local.php`
- Admin notifications go to `mail.admin_notify_email`
