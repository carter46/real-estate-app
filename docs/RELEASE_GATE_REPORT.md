# Release Gate Report — SDC Real Estate App

**Date:** 2026-08-15  
**Scope:** Final Pre-Production Release Gate (Hardened A–N)  
**App path:** `real-estate-app/`

---

## Executive Verdict

# NO-GO

**Reason (critical):** Runtime testing is **completely impossible** on the audit host (no PHP / Composer / MySQL on PATH; no `vendor/`; no `config.local.php`). Per hardened rule **N/K**, production must not be recommended without execution evidence for lineage, CMS mutation, branding propagation, and email generation.

Mandatory CMS gaps from the pre-gate audit were **implemented in code** during this pass (settings SSoT, profile, type/amenity CRUD, branding uploads). That makes the tree **code-complete for the gate features**, but **code completeness ≠ GO**.

---

## Overall Score

| Category | Weight | Score | Notes |
|----------|--------|-------|-------|
| Functionality (static) | 15% | 12/15 | CRUD/public paths present; unexecuted |
| Database design | 10% | 9/10 | Schema + migration for `is_active`; unexecuted import |
| CMS | 15% | 13/15 | Settings/profile/types/amenities implemented; unexecuted |
| Dynamic content | 15% | 12/15 | Property queries DB-driven (static); branding SSoT wired |
| UI fidelity | 10% | 0/10 | **UNABLE TO TEST** (no browser/runtime) |
| Security (static) | 15% | 12/15 | Phase 7 + new auth_require pages; unexecuted |
| Email | 10% | 4/10 | Paths dynamic (static); delivery **UNABLE TO TEST** |
| Performance | 5% | 3/5 | List queries avoid amenity/image row multiply (static) |
| Deploy readiness | 5% | 2/5 | DEPLOY.md present; no live stack |

**Weighted ≈ 67%** — capped because runtime = 0. Do not interpret as “ready to ship.”

---

## Critical Findings

1. **Zero runtime verification** — PHP/Composer/MySQL unavailable; `vendor/` absent; cannot PASS smoke, lineage×5, CMS mutation, branding E2E, or email generation.  
2. **Production recommendation blocked** by gate rule until a lab/host runs the matrix in `docs/PHASE7_QA.md` plus branding/CMS mutation tests.

---

## High-Priority Findings

1. Existing databases must run `database/migrations_release_gate.sql` (`is_active` on types/amenities; new settings keys) before taxonomy CMS works.  
2. Seed still uses placeholder phone/email (`800.555.0123`, `info@example.com`) — **category 2/3** defaults; replace via Admin → Settings before go-live.  
3. `composer.lock` still absent — pin dependencies on first successful `composer install`.  
4. Tailwind CDN / Google Fonts / no strict CSP — accepted residual (not a CMS blocker).  
5. Footer Privacy / Fair Housing remain non-linked labels (scope lock M).  
6. Property detail map is a placeholder slot only.

---

## Medium / Low Findings

| Item | Class | Notes |
|------|-------|-------|
| Marketing stats `$2B+`, `40+` on about/home | 1 — static marketing | Allowed |
| Default logo `assets/img/logo-sdc.svg` | 2 — fallback until Settings upload | Correct |
| `config.php` `app.name` | 2 — bootstrap fallback when settings empty | Correct; UI uses `site_name()` |
| Office names fallback in footer when DB empty | 2 — empty-state fallback | OK |
| Comment “Glass House template” | 4 — docs/comment | Not listing data |
| BHHS mention only in UI_FIDELITY.md | 4 — documentation | OK |
| Admin list pagination hard-capped ~50 | Medium | Pre-existing |

---

## Hardcoded Data Findings (classification J)

| Finding | Class | Action |
|---------|-------|--------|
| Property titles/prices in PHP templates | — | **None found** (DB-driven cards) |
| Seed property rows | 3 | Intentional |
| Site name in titles/header/footer/mail | Was 5 | **Corrected** → `site_name()` / settings |
| Logo path hardcoded in header/footer | Was 5 | **Corrected** → `site_logo_url()` |
| No favicon | Was 5 | **Corrected** → settings + `<link rel="icon">` |
| Inquiry email “SDC” subjects | Was 5 | **Corrected** → `site_name()` / `site_mail_from_name()` |
| About/home hero brand sentences | Was mixed | **Corrected** to `site_name()` where branding |
| `$2B+` marketing | 1 | Left static |

---

## Dynamic Settings Findings

| Capability | Implemented | Runtime verified |
|------------|-------------|------------------|
| Site name SSoT (`settings` + Admin → Settings) | **Yes** | **UNABLE TO TEST** |
| Logo upload + replace (deletes prior `uploads/branding/` file) | **Yes** | **UNABLE TO TEST** |
| Favicon upload + replace | **Yes** | **UNABLE TO TEST** |
| Public phone / email | **Yes** | **UNABLE TO TEST** |
| Mail display name | **Yes** | **UNABLE TO TEST** |
| Propagation to header/footer/title/favicon/mail | **Wired in code** | **UNABLE TO TEST** |

---

## Property Data Findings (static)

| Check | Result | Evidence type |
|-------|--------|---------------|
| Home featured from MySQL | Passed (static) | `index.php` → `property_list_public` |
| Listings/search from MySQL | Passed (static) | `properties.php` → count/list public |
| Detail by slug from MySQL | Passed (static) | `property_find_public_by_slug` |
| No hardcoded listing cards | Passed (static) | Card partials use `$property` |
| List SQL duplicate risk (amenities/images joins) | Passed (static) | Correlated subqueries + types 1:1 join only |
| Visibility draft/private/archived excluded | Passed (static) | `config.php` + `public_status_sql_in` |
| Admin edit updates same ID | Passed (static) | `property_update($id, …)` |
| Lineage ×5 end-to-end | **UNABLE TO TEST** | No DB |
| CMS mutation publish/edit/archive | **UNABLE TO TEST** | No runtime |

---

## CMS Findings

| Feature | Implemented | Runtime verified |
|---------|-------------|------------------|
| Admin login / logout POST+CSRF | Yes (prior) | UNABLE TO TEST |
| Dashboard KPIs from MySQL | Yes (prior) | UNABLE TO TEST |
| Property list / add / edit / archive | Yes (prior) | UNABLE TO TEST |
| Image gallery + cover | Yes (prior) | UNABLE TO TEST |
| Listing purpose enum sale/rent/lease | Yes (form, not CMS table) | UNABLE TO TEST |
| Property types View/Add/Edit/Deactivate | **Yes (this pass)** | UNABLE TO TEST |
| Amenities View/Add/Edit/Deactivate | **Yes (this pass)** | UNABLE TO TEST |
| Deactivate without orphaning FKs | **Yes** (soft deactivate only) | UNABLE TO TEST |
| Website Settings | **Yes** | UNABLE TO TEST |
| Admin profile email/password | **Yes** | UNABLE TO TEST |
| Inquiry management | Yes (prior) | UNABLE TO TEST |
| Empty / error states | Code paths present | UNABLE TO TEST |

---

## Email Findings

| Flow | Implemented | Generated mail inspected |
|------|-------------|--------------------------|
| Contact → DB + admin notify + client ack | Yes | **UNABLE TO TEST** |
| Property inquiry → same | Yes | **UNABLE TO TEST** |
| Admin reply | Yes | **UNABLE TO TEST** |
| From name from settings | Yes (`site_mail_from_name`) | UNABLE TO TEST |
| Notify recipient config → settings fallback | Yes | UNABLE TO TEST |
| Log driver records from= in mail.log | Yes (code) | UNABLE TO TEST |
| SMTP/Brevo live delivery | Config-ready | UNABLE TO TEST |

---

## Security Findings (static)

| Control | Status |
|---------|--------|
| Password hashing (bcrypt) | Present |
| Session regenerate on login; password change forces re-login | Present |
| Idle timeout + periodic active-user recheck | Present |
| CSRF on POSTs including logout | Present |
| Prepared statements | Present |
| Upload MIME + getimagesize; branding SVG script reject | Present |
| Uploads / branding `.htaccess` deny PHP | Present |
| Root `.htaccess` deny database/storage/vendor | Present |
| Production `display_errors=0` when env=production | Present |
| Login / inquiry rate limits + honeypot | Present |
| Live authz / upload exploit tests | **UNABLE TO TEST** |

---

## UI Fidelity Findings

| Page | Reference | PHP | Visual fidelity | Dynamic data | Missing |
|------|-----------|-----|-----------------|--------------|---------|
| Home | `references/homepage` | `index.php` | **UNABLE TO TEST** | Featured from DB (static) | Runtime render |
| Listings | `property_listings` | `properties.php` | UNABLE TO TEST | DB filters (static) | Runtime |
| Detail | `the_glass_house…` | `property.php` | UNABLE TO TEST | DB + inquiry | Map interactive |
| Agents | `our_experts` | `agents.php` | UNABLE TO TEST | DB | Runtime |
| About | `about_us` | `about.php` | UNABLE TO TEST | Branding helpers | Runtime |
| Contact | `contact_us` | `contact.php` | UNABLE TO TEST | Settings+DB | Runtime |
| FAQ | `faq` | `faq.php` | UNABLE TO TEST | Static FAQ content | Runtime |
| Admin * | `admin_*` | `admin/*` | UNABLE TO TEST | Live queries (static) | Runtime |

Structural Tailwind/token approach remains; **no PASS** for visual 1:1 without browser.

---

## Testing Matrix

| Test | Result | Evidence | Notes |
|------|--------|----------|-------|
| Inventory routes | Passed (static) | `docs/ROUTES.md` | Includes new CMS routes |
| `href="#"` / void links in app PHP | Passed (static) | ripgrep no matches | |
| Property hardcode scan | Passed (static) | No listing prices/titles in templates | |
| Visibility rules | Passed (static) | config + public queries | |
| List join duplicate risk | Passed (static) | No multi-row amenity/image join | |
| Settings SSoT wiring | Passed (static) | settings.php + header/footer/mail | |
| Taxonomy CRUD code | Passed (static) | admin property-types/amenities | |
| Profile / password change code | Passed (static) | admin/profile.php | |
| Branding upload replace cleanup | Passed (static) | branding_upload_image | |
| `php bin/smoke.php` | **UNABLE TO TEST** | No PHP on PATH | |
| `php health.php` | **UNABLE TO TEST** | No PHP | |
| Branding propagation E2E | **UNABLE TO TEST** | No runtime | |
| Lineage ×5 properties | **UNABLE TO TEST** | No DB | |
| CMS mutation create→archive | **UNABLE TO TEST** | No runtime | |
| Email generated body inspection | **UNABLE TO TEST** | No mail.log generation | |
| Upload replacement on disk | **UNABLE TO TEST** | | |
| UI fidelity vs screen.png | **UNABLE TO TEST** | No browser | |
| Empty DB / error states live | **UNABLE TO TEST** | | |
| SMTP delivery | **UNABLE TO TEST** | | |

---

## Files Changed (this gate pass)

**Created:**  
`includes/settings.php`, `includes/taxonomy.php`, `admin/settings.php`, `admin/profile.php`, `admin/property-types.php`, `admin/amenities.php`, `uploads/branding/.htaccess`, `uploads/branding/.gitkeep`, `database/migrations_release_gate.sql`, `docs/RELEASE_GATE_REPORT.md` (this file)

**Updated:**  
`includes/bootstrap.php`, `uploads.php`, `properties.php`, `mailer.php`, `inquiries.php`, `header.php`, `footer.php`, `stitch-head.php`, `admin-header.php`, `admin-nav.php`, `admin/property-form.php`, `admin/login.php`, `admin/index.php`, `admin/inquiries.php`, `contact.php`, `index.php`, `about.php`, `properties.php`, `property.php`, `database/schema.sql`, `database/seed.sql`, `docs/ROUTES.md`, `DEPLOY.md`, `.gitignore`

**Not modified:** `references/**`

---

## Remaining Limitations

- No PHP / Composer / MySQL / browser / SMTP on the audit machine  
- No `vendor/`, no `config.local.php`  
- Visual Stitch fidelity and all E2E CMS/branding/email flows remain unproven  
- CDN CSS / map / legal pages deferred by scope

---

## Deployment Requirements (when a real host exists)

1. PHP 8.1+, MySQL, Composer; `composer install --no-dev`  
2. `config.local.production.php.example` → `config.local.php`  
3. Import `schema.sql` (+ optional `seed.sql`) **or** migrate existing DB with `migrations_release_gate.sql`  
4. Writable `uploads/properties`, `uploads/branding`, `storage/logs`, `storage/rate_limits`  
5. First admin via `/admin/setup.php`  
6. Admin → Settings: real name, phone, email, logo, favicon  
7. Execute full matrix: smoke, health, lineage×5, CMS mutation, branding propagation, contact+property inquiry (inspect `mail.log` or SMTP), profile password change  
8. Only then re-run this gate for a possible **GO** / **GO WITH WARNINGS**

---

## Final Recommendation

**NO-GO for production deployment.**

The application is substantially closer to release after implementing mandatory CMS and branding SSoT, but **this environment produced no executed runtime evidence**. Do not deploy until the testing matrix rows currently marked **UNABLE TO TEST** are executed on a working stack and this report is updated with a new verdict.

**STOP.** No deploy. No push. No next feature phase.
