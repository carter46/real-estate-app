# Public & admin routes — SDC Real Estate App

Document root = `real-estate-app/`.

## Public

| Route | File | Notes |
|-------|------|-------|
| `/` · `/index.php` | `index.php` | Home (7 sections); featured from DB |
| `/properties.php` | `properties.php` | Filters, sort, load more |
| `/property.php?slug=` | `property.php` | Detail + inquiry |
| `/agents.php` | `agents.php` | Region filter |
| `/about.php` | `about.php` | Editorial |
| `/faq.php` | `faq.php` | Accordion |
| `/contact.php` | `contact.php` | Form + offices |
| `/health.php` | `health.php` | Restricted |

## Admin

| Route | File | Notes |
|-------|------|-------|
| `/admin/setup.php` | One-time setup |
| `/admin/login.php` | Login |
| `/admin/logout.php` | Logout POST+CSRF |
| `/admin/index.php` | Dashboard KPIs |
| `/admin/properties.php` | Property list |
| `/admin/property-form.php` | Create/edit + gallery |
| `/admin/property-archive.php` | Soft archive |
| `/admin/property-types.php` | Property types CRUD (deactivate) |
| `/admin/regions.php` | Regions / destinations CRUD (deactivate) |
| `/admin/amenities.php` | Amenities CRUD (deactivate) |
| `/admin/inquiries.php` | Inquiries |
| `/admin/settings.php` | Website branding SSoT |
| `/admin/profile.php` | Account email/password |

## CLI

| Command | File |
|---------|------|
| `php bin/smoke.php` | Smoke |
| `php health.php` | Health |

See [`DEPLOY.md`](../DEPLOY.md). Existing DBs: run [`database/migrations_release_gate.sql`](../database/migrations_release_gate.sql).
