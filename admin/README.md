# Admin area

**Sunview Development and Consultancy (SDC)**

## Routes

| File | Purpose |
|------|---------|
| `setup.php` | One-time first admin creation |
| `login.php` / `logout.php` | Session auth |
| `index.php` | Dashboard overview (live counts) |
| `properties.php` | Property list / search / filter |
| `property-form.php` | Add / edit property + gallery |
| `property-archive.php` | Soft-archive (`status=archived`) |
| `inquiries.php` | Inquiry inbox + detail, status, notes, email reply |

## Inquiries (Phase 5)

- Public contact form → `inquiries` + admin notify + client ack
- Property detail inquiry form → `property_inquiry`
- Admin list/detail, status, notes, Send Reply (mailer)

- Unique `slug`, `reference_code`, `mls_number` (MLS stored as NULL when empty)
- Soft duplicate warning on same address + city (confirm checkbox to proceed)
- Statuses include draft / available / pending / under_contract / sold / private / archived
- Featured flag, amenities, agent + agent quote
- Gallery: upload (JPEG/PNG/WebP), caption, set cover, delete; one cover enforced in app logic
- Public “View” only when status is publicly visible

## Sidebar IA

Dashboard Overview · Manage Properties · Inquiries · Exit to Site
