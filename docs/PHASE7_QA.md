# Phase 7 — QA Checklist (SDC)

Run through these on a machine with PHP 8.1+, MySQL, and Composer. Mark each item when verified.

## Automated / CLI

- [ ] `composer install`
- [ ] Import `database/schema.sql` then `database/seed.sql`
- [ ] Copy `config.local.php.example` → `config.local.php` (local env, DB, mail driver `log`)
- [ ] `php bin/smoke.php` exits 0
- [ ] `php health.php` prints OK (or JSON via browser with admin / token / local debug)

## Auth & session

- [ ] First visit: `admin/setup.php` creates admin (≥12 char password); setup then blocked
- [ ] Wrong password shows generic error; after 5 failures, lockout message
- [ ] Successful login → dashboard; idle session expires after 8 hours (spot-check config)
- [ ] Sign out is POST+CSRF (button in top bar); GET `/admin/logout.php` does not sign out
- [ ] Unauthenticated `/admin/index.php` redirects to login

## Property CRUD

- [ ] Create property (draft) → not on public listings / home featured
- [ ] Publish (available) → appears on `properties.php` and detail by slug
- [ ] Duplicate address warning + confirm checkbox
- [ ] Duplicate slug / reference / MLS rejected
- [ ] Empty MLS stored as NULL (second empty MLS allowed)
- [ ] Upload JPEG/PNG/WebP OK; reject `.php` / oversized / non-image
- [ ] Set cover, caption, delete image
- [ ] Archive via archive action → disappears from public; featured cleared
- [ ] Status dropdown does not offer Archive for new listings (use Archive action)

## Search & public

- [ ] Filters: region, type, price (`2-5`, `5-10`, `10+`), sort, load more
- [ ] Home quick-search uses same price keys as listings
- [ ] Invalid slug → 404 copy (not redirect to home)
- [ ] Draft / private / archived never on public list or detail
- [ ] Agents region filter; contact + property inquiry with honeypot left empty
- [ ] Honeypot filled → fake success, no inquiry row
- [ ] >5 inquiries/hour from same IP → rate limit message

## Email / inquiries admin

- [ ] Contact + property inquiry create rows; `storage/logs/mail.log` entries when driver=`log`
- [ ] Admin inquiries: status, notes, reply (SMTP or log); UI never shows raw SMTP exception text

## Security static checks

- [ ] `robots.txt` disallows `/admin/`
- [ ] Apache: `/database/`, `/storage/`, `/vendor/`, `config.local.php` return 403 when docroot is app folder
- [ ] Response headers include `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`
- [ ] `uploads/` cannot execute PHP (try uploading renamed script — blocked by validation + `.htaccess`)

## Responsive / UI (manual)

- [ ] Home: all 7 sections; mobile menu works
- [ ] Listings / detail / contact / agents / about / FAQ usable at ~375px and desktop
- [ ] Admin tables/forms usable on tablet width

## Known deferred (optional post-launch)

- Tailwind CDN → compiled CSS; self-host fonts / icons
- Interactive map on property detail
- Strict Content-Security-Policy
- Privacy Policy / Fair Housing pages (footer labels only today)

See [`DEPLOY.md`](../DEPLOY.md).