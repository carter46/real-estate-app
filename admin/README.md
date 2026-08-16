# Admin area

**Sunview Development and Consultancy (SDC)**

Full route map: [`../docs/ROUTES.md`](../docs/ROUTES.md) · Deploy: [`../DEPLOY.md`](../DEPLOY.md)

## Routes

| File | Purpose |
|------|---------|
| `setup.php` | One-time first admin creation |
| `login.php` / `logout.php` | Session auth (logout = POST + CSRF) |
| `index.php` | Dashboard overview (live counts) |
| `properties.php` | Property list / search / filter |
| `property-form.php` | Add / edit property + gallery |
| `property-archive.php` | Soft-archive (`status=archived`) |
| `inquiries.php` | Inquiry inbox + detail, status, notes, email reply |

## Properties

- Unique `slug`, `reference_code`, `mls_number` (MLS stored as NULL when empty)
- Soft duplicate warning on same address + city (confirm checkbox to proceed)
- Statuses: draft / available / pending / under_contract / sold / private / archived (archive via archive action)
- Featured flag, amenities, agent + agent quote
- Gallery: upload (JPEG/PNG/WebP), caption, set cover, delete; one cover enforced in app logic

## Inquiries

- Public contact + property detail forms → `inquiries` table + mail notify/ack
- Admin list/detail, status, notes, Send Reply

## Sidebar IA

Dashboard Overview · Manage Properties · Inquiries · Exit to Site
