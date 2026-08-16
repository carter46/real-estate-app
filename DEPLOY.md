# Deployment Guide — SDC Real Estate App

**Sunview Development and Consultancy (SDC)**  
PHP + MySQL property management platform.

Visual reference folder `../references/` is **read-only** and must not be modified or deleted during deploy.

## 1. Requirements

| Component | Version / notes |
|-----------|-----------------|
| PHP | 8.1+ with extensions: `pdo_mysql`, `fileinfo`, `gd` (recommended for upload re-encode), `mbstring` optional |
| MySQL | 8.0+ (or MariaDB 10.4+) |
| Composer | 2.x |
| Web server | Apache 2.4+ (mod_rewrite) or Nginx + PHP-FPM |
| TLS | HTTPS required in production |

## 2. What to deploy

Copy the `real-estate-app/` tree to the server.

**Do not deploy / commit:**

- `config.local.php` (create on server with production secrets)
- `uploads/properties/*` and `uploads/branding/*` contents from another environment unless intentional
- `storage/logs/*`, `storage/rate_limits/*` runtime files
- `.git/` (optional)

**Git / panel deploys that run Composer** (e.g. Hostinger Git): do **not** commit `vendor/`. Leave `composer.json` in the repo and let the host run `composer install`. A hand-rolled or BOM-corrupted `vendor/` will break deploy.

**Hosts with no Composer:** build `vendor/` with a real `composer install --no-dev` on a machine that has Composer, then upload that tree separately (do not invent `installed.json` by hand).

**Never delete** sibling `references/` unless explicitly requested by the project owner.

## 3. Configuration

1. Copy `config.local.production.php.example` → `config.local.php` on the server  
   (or start from `config.local.php.example` and switch values to production).
2. Set at minimum:

| Key | Production value |
|-----|------------------|
| `app.env` | `production` (forces `debug=false`) |
| `app.debug` | `false` |
| `app.url` | Public HTTPS origin, **no trailing slash** (e.g. `https://www.example.com`) |
| `db.*` | Dedicated DB user/password (not empty root) |
| `mail.driver` | `smtp` (or keep `log` only for staging) |
| `mail.from_email` / `admin_notify_email` | Real addresses |
| `security.cookie_secure` | `true` or `null` (auto HTTPS) |
| `security.trust_forwarded_proto` | `true` **only** behind a trusted TLS-terminating proxy |
| `security.health_token` | Long random string for monitoring |

Committed `config.php` defaults are already production-safe. Secrets belong only in `config.local.php`.

After seed, update `settings` via **Admin → Website Settings** (site name, phone, email, logo, favicon). Do not hardcode branding in templates.

Existing databases upgrading from an earlier Phase 8 tree must run:

```bash
mysql -u USER -p real_estate < database/migrations_release_gate.sql
```

(Ignore duplicate-column errors if columns already exist.)


## 4. Database

```bash
mysql -u USER -p < database/schema.sql
# Optional demo data (skip on clean production if preferred):
mysql -u USER -p < database/seed.sql
```

- No admin password is seeded. Create the first admin via `/admin/setup.php` once.
- After setup completes, further setup visits are blocked by the app.

## 5. Composer / `vendor/`

On a machine with Composer (local PC or CI):

```bash
cd /path/to/real-estate-app
composer install --no-dev --optimize-autoloader
```

Your staging host already runs Composer on deploy — keep `vendor/` out of git (see `.gitignore`) so install is clean. SMTP needs PHPMailer under `vendor/` after that install. The `log` mail driver can run without PHPMailer for smoke tests only.

If `composer.lock` is present, use it for reproducible installs. If absent, pin/commit a lock when available.

## 6. Permissions

Web / PHP user must **write**:

- `uploads/properties/`
- `uploads/branding/`
- `storage/logs/`
- `storage/rate_limits/`

Recommended (adjust user/group to your host):

```bash
chown -R www-data:www-data uploads storage
find uploads storage -type d -exec chmod 775 {} \;
find uploads storage -type f -exec chmod 664 {} \;
```

Application PHP files should not be world-writable (`644` / dirs `755`).

## 7. Web server

### Document root

Point the vhost **document root** at `real-estate-app/` (this folder).

### Apache

See [`docs/apache-vhost.example.conf`](docs/apache-vhost.example.conf).  
Existing `.htaccess` files deny `database/`, `storage/`, `vendor/`, `bin/`, and `config.local.php` when `AllowOverride` permits them. `uploads/.htaccess` disables script execution; `storage/.htaccess` denies all.

### Nginx

See [`docs/nginx.example.conf`](docs/nginx.example.conf).  
Nginx ignores `.htaccess` — the example replicates deny rules and turns off PHP under `uploads/`.

## 8. First-run & post-deploy checks

1. Open the site homepage (`app.url`).
2. Visit `/admin/setup.php` → create admin (≥12 character password).
3. Sign in → add/publish a property → confirm public listing + detail.
4. Submit contact form → check `storage/logs/mail.log` (log driver) or inbox (SMTP).
5. CLI:

```bash
php bin/smoke.php
php health.php
# Web (optional): /health.php?token=YOUR_HEALTH_TOKEN
```

6. Work through residual items in [`docs/PHASE7_QA.md`](docs/PHASE7_QA.md) if not already signed off.

## 9. Routes (entrypoint map)

### Public

| URL | File |
|-----|------|
| `/` or `/index.php` | `index.php` |
| `/properties.php` | Listings |
| `/property.php?slug=` | Detail + inquiry |
| `/agents.php` | Agents |
| `/about.php` | About |
| `/faq.php` | FAQ |
| `/contact.php` | Contact |
| `/health.php` | Health (restricted) |

### Admin

| URL | File |
|-----|------|
| `/admin/setup.php` | One-time setup |
| `/admin/login.php` | Login |
| `/admin/logout.php` | Logout (**POST + CSRF only**) |
| `/admin/index.php` | Dashboard |
| `/admin/properties.php` | Manage properties |
| `/admin/property-form.php` | Create/edit + gallery |
| `/admin/property-archive.php` | Archive |
| `/admin/inquiries.php` | Inquiries |

### CLI only (block via web)

- `bin/smoke.php`

Footer “Privacy Policy” / “Fair Housing” are non-linked labels (no pages yet — do not invent them without a product decision).

## 10. Assets / CSS (accepted residual)

Public UI uses **Tailwind CDN** + Google Fonts + Material Symbols + `assets/css/public-overrides.css` (Phase 6 fidelity).

Optional later hardening (not required to go live):

- Compile Tailwind → `assets/css/public.built.css` and remove the CDN script
- Self-host fonts under `assets/fonts/`
- Add a strict Content-Security-Policy once CDN/fonts are local

## 11. Security checklist (production)

- [ ] HTTPS everywhere; `app.url` matches the public origin
- [ ] `app.env=production`, `debug=false`
- [ ] Strong DB password; least-privilege DB user
- [ ] SMTP credentials set; test inquiry email
- [ ] `uploads/` cannot execute PHP
- [ ] `database/`, `storage/`, `vendor/`, `bin/`, `config.local.php` not publicly readable
- [ ] `robots.txt` disallows `/admin/`
- [ ] Admin setup completed; bookmark removed from public marketing if desired
- [ ] Backups: MySQL dump + `uploads/properties/`

## 12. Rollback notes

- Keep previous release directory or git tag
- Restore previous `config.local.php` and DB dump if schema changed (this release does not require schema migrations beyond initial `schema.sql`)
