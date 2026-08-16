# UI Fidelity Rules — SDC Real Estate App

**Brand:** Sunview Development and Consultancy (**SDC**)

Visual fidelity is a **hard project requirement**. This is not a redesign.

## Pipeline

```text
Reference screenshot / rendered UI (screen.png)
  → reference HTML structure (code.html)
  → PHP templates / components
  → MySQL dynamic data
  → same visual result
```

## Source-of-truth hierarchy

1. `references/*/screen.png` / rendered appearance  
2. `references/*/code.html`  
3. `references/high_alpine_editorial/DESIGN.md`

If they conflict, follow the rendered reference unless the discrepancy is documented here. **DESIGN.md must not override what the reference visibly looks like.**

Known DESIGN.md prose vs HTML conflicts (follow HTML):

| Topic | DESIGN.md | HTML | Follow |
|-------|-----------|------|--------|
| Primary CTA | `#521A2D` | `#370518` | HTML |
| Cream | `#F9F6F0` | `#fcf9f8` | HTML |
| Radii | Sharp 0 | many `rounded-*` | HTML |
| Shadows | Rare Cabernet tint | Tailwind shadows | HTML |
| Inputs | Bottom-border only | Mixed by page | Per-page HTML |
| Motion | 300ms | Often 500–700ms | Per-page HTML |

If a design element cannot be reproduced exactly for a technical reason, **report it** — do not silently change the design.

## Homepage — all seven sections required

1. Fixed header  
2. Hero  
3. Overlapping quick-search  
4. Exclusive Collections  
5. Curated For You  
6. Unmatched Market Expertise  
7. Footer  

Do not reduce the homepage to hero + cards.

## Property card variants (keep separate)

| Partial | Reference | Structure |
|---------|-----------|-----------|
| `includes/property-card-home.php` | homepage | image → badge → favorite → price → address line → icon specs |
| `includes/property-card-list.php` | property_listings | image → badge → favorite → price → title → City, ST ZIP → numeric specs |
| `includes/property-card-featured.php` | property_listings featured | wide media + panel, description, acres, View Details |

Do **not** merge these into one generic card. `includes/property-card.php` throws if included.

## Dynamic data rules

- DB values fill existing visual slots only.  
- Truncation/wrapping must match reference behavior.  
- Image aspect ratios and cropping must match reference.  
- Do not change component structure because a title or description is longer.

## Page map

| Reference | PHP page | Key components | Primary tables |
|-----------|----------|----------------|----------------|
| homepage | `index.php` | header, hero, quick-search, collections, card-home, editorial, footer | properties, images, types, offices, settings |
| property_listings | `properties.php` | filters, card-list, card-featured, load-more | properties, images, types |
| the_glass_house… | `property.php?slug=` | hero, spec-bar, vision, gallery (caption), amenities, agent card | properties, images, amenities, agents, inquiries |
| about_us | `about.php` | static sections | settings / static |
| contact_us | `contact.php` | offices, contact form | offices, settings, inquiries |
| our_experts | `agents.php` | agent cards, region filter | agents |
| faq | `faq.php` | accordion | static (unless later CMS) |
| admin_* | `admin/*` | admin-shell, tables, forms | properties, inquiries, users |

## Phase 6 status (public UI) — accepted for production v1

**Done:**

- Public pages use `includes/stitch-head.php` — Tailwind CDN + **exact** reference token config from homepage `code.html`
- Header / footer / card variants / search bar / page bodies use Stitch utility classes
- SDC logo at `assets/img/logo-sdc.svg` (reference BHHS branding not used in app chrome)
- `assets/css/public-overrides.css` for placeholders, hero/collection gradients, clamps
- Admin keeps token-aligned `admin.css` shell

**Optional later (not required to go live):**

- Replace Tailwind CDN with built/local CSS  
- Self-host Google Fonts / Material Symbols  
- Localize remote demo images into `uploads/` when desired  
- Interactive map on property detail (layout slot preserved)  
- Strict Content-Security-Policy after CDN/fonts are local  

See [`DEPLOY.md`](../DEPLOY.md) §10.

## Application rules documented for Phase 3+

- Exactly one cover image per property: enforce in app logic when uploading  
- Store `mls_number` as `NULL` when empty (not `''`) to avoid UNIQUE collisions  
