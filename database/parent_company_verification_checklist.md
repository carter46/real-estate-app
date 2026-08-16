# Parent-company import — verification checklist

Run after deploying images + `migrations_parent_company_properties.sql`.

## Database

- [ ] `SELECT COUNT(*) FROM properties WHERE source_name='century_communities';` → **25**
- [ ] `SELECT state, COUNT(*) FROM properties WHERE source_name='century_communities' GROUP BY state;` → AZ/CO/TX/FL/GA = 5 each
- [ ] `SELECT COUNT(*) FROM properties WHERE source_name='century_communities' AND is_featured=1;` → **5**
- [ ] Re-run migration once → still **25** (no duplicates)
- [ ] Existing Aspen/Vail seed properties still present

## Admin

- [ ] Admin → Properties lists the new titles
- [ ] Open one import (e.g. Aguila) → title, price, beds/baths/sqft, region, images
- [ ] Import source note shows `century_communities` + parent link

## Public site

- [ ] Homepage featured strip ≤ 6 and includes import featured homes without flooding all 25
- [ ] Exclusive Collections shows Phoenix Metro / Denver Metro / Austin Metro / Central Florida / Atlanta Metro
- [ ] Properties page Destination filter: each region returns 5 imports
- [ ] Search `Colorado` / `Denver` finds Windler listings
- [ ] Property detail: cover + gallery, price, specs, agent assigned
- [ ] Slugs resolve (e.g. `/property.php?slug=cc-tx-stallion-run-dartford` or pretty URL equivalent)

## Filters

- [ ] Property type Single Family includes imports
- [ ] Listing purpose sale / status available
- [ ] Bedroom search (e.g. 4 bed) returns matching imports

## Agents

- [ ] Round-robin spread: Eleanor 7, Julian 6, Chloe 6, Marcus 6 among the 25
