# Parent-company inventory import (Century Communities)
# Idempotent: safe to re-run — upserts by reference_code / slug; replaces images only for this import set.
#
# PREAMBLE (DDL may auto-commit on some hosts — run this block first if needed):
#   ALTER for source_* columns if missing.
#
# Then run the DML transaction below.
# Requires: property_types.single_family, agents from seed (or existing), uploads/ images deployed.
#
# Preserve existing non-import properties. Does NOT wipe seed data.

-- ---------------------------------------------------------------------------
-- 0) Schema: source attribution columns (idempotent)
-- ---------------------------------------------------------------------------
SET @db := DATABASE();

SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'properties' AND COLUMN_NAME = 'source_name'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE `properties`
     ADD COLUMN `source_name` VARCHAR(80) NULL DEFAULT NULL COMMENT ''Internal import source'',
     ADD COLUMN `source_url` VARCHAR(500) NULL DEFAULT NULL COMMENT ''Parent listing URL'',
     ADD COLUMN `source_reference` VARCHAR(120) NULL DEFAULT NULL COMMENT ''Stable source key'',
     ADD KEY `idx_properties_source` (`source_name`, `source_reference`)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Ensure single_family type exists
INSERT INTO `property_types` (`slug`, `name`, `sort_order`, `is_active`)
SELECT 'single_family', 'Single Family', 50, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `property_types` WHERE `slug` = 'single_family' LIMIT 1);

-- ---------------------------------------------------------------------------
-- 1) Transactional DML
-- ---------------------------------------------------------------------------
START TRANSACTION;

-- Regions (upsert by slug — do not delete Aspen/Vail/etc.)
INSERT INTO `regions` (`slug`, `name`, `sort_order`, `is_active`, `is_featured`, `image_path`) VALUES
  ('phoenix-metro', 'Phoenix Metro', 100, 1, 1, 'uploads/regions/phoenix-metro.jpg'),
  ('denver-metro', 'Denver Metro', 110, 1, 1, 'uploads/regions/denver-metro.jpg'),
  ('austin-metro', 'Austin Metro', 120, 1, 1, 'uploads/regions/austin-metro.jpg'),
  ('central-florida', 'Central Florida', 130, 1, 1, 'uploads/regions/central-florida.jpg'),
  ('atlanta-metro', 'Atlanta Metro', 140, 1, 1, 'uploads/regions/atlanta-metro.jpg')
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `sort_order` = VALUES(`sort_order`),
  `is_active` = 1,
  `is_featured` = VALUES(`is_featured`),
  `image_path` = VALUES(`image_path`);

SET @type_sf := (SELECT id FROM `property_types` WHERE slug = 'single_family' LIMIT 1);
SET @agent_eleanor := (SELECT id FROM `agents` WHERE slug = 'eleanor-vance' LIMIT 1);
SET @agent_julian  := (SELECT id FROM `agents` WHERE slug = 'julian-thorne' LIMIT 1);
SET @agent_chloe   := (SELECT id FROM `agents` WHERE slug = 'chloe-sterling' LIMIT 1);
SET @agent_marcus  := (SELECT id FROM `agents` WHERE slug = 'marcus-wright' LIMIT 1);

-- Helper: upsert property by reference_code
-- Note: MySQL INSERT...ON DUPLICATE KEY UPDATE on reference_code unique.

INSERT INTO `properties` (
  `slug`, `reference_code`, `mls_number`, `title`, `description`, `property_type_id`, `listing_purpose`,
  `status`, `price`, `price_on_request`, `currency`,
  `address_line`, `city`, `region`, `state`, `postal_code`, `country`,
  `bedrooms`, `bathrooms`, `sqft`, `lot_acres`, `year_built`, `badge`, `is_featured`,
  `agent_id`, `listed_at`, `source_name`, `source_url`, `source_reference`
) VALUES
-- AZ
('cc-az-coolidge-gateway-aguila', 'CC-AZ-AGUILA', NULL, 'Aguila at Coolidge Gateway Manor',
 'New single-family floor plan at Coolidge Gateway Manor in Coolidge, AZ. Starting from published builder pricing. Specs as listed by the builder; square footage is approximate.',
 @type_sf, 'sale', 'available', 291990.00, 0, 'USD',
 'Arizona Boulevard & N Gateway Manor Place', 'Coolidge', 'Phoenix Metro', 'AZ', '85128', 'USA',
 4.0, 3.0, 1778, NULL, NULL, NULL, 1, @agent_eleanor, CURDATE(),
 'century_communities', 'https://www.centurycommunities.com/find-your-new-home/arizona/phoenix-metro/coolidge/coolidge-gateway-manor/plans/aguila/', 'aguila'),
('cc-az-coolidge-gateway-jasmine', 'CC-AZ-JASMINE', NULL, 'Jasmine at Coolidge Gateway Manor',
 'New single-family floor plan at Coolidge Gateway Manor in Coolidge, AZ. Starting from published builder pricing. Specs as listed by the builder; square footage is approximate.',
 @type_sf, 'sale', 'available', 301990.00, 0, 'USD',
 'Arizona Boulevard & N Gateway Manor Place', 'Coolidge', 'Phoenix Metro', 'AZ', '85128', 'USA',
 4.0, 2.5, 2016, NULL, NULL, NULL, 0, @agent_julian, CURDATE(),
 'century_communities', 'https://www.centurycommunities.com/find-your-new-home/arizona/phoenix-metro/coolidge/coolidge-gateway-manor/plans/jasmine/', 'jasmine'),
('cc-az-coolidge-gateway-sage', 'CC-AZ-SAGE', NULL, 'Sage at Coolidge Gateway Manor',
 'New single-family floor plan at Coolidge Gateway Manor in Coolidge, AZ. Starting from published builder pricing. Specs as listed by the builder; square footage is approximate.',
 @type_sf, 'sale', 'available', 316990.00, 0, 'USD',
 'Arizona Boulevard & N Gateway Manor Place', 'Coolidge', 'Phoenix Metro', 'AZ', '85128', 'USA',
 5.0, 3.0, 2179, NULL, NULL, NULL, 0, @agent_chloe, CURDATE(),
 'century_communities', 'https://www.centurycommunities.com/find-your-new-home/arizona/phoenix-metro/coolidge/coolidge-gateway-manor/plans/sage/', 'sage'),
('cc-az-coolidge-gateway-troon', 'CC-AZ-TROON', NULL, 'Troon at Coolidge Gateway Manor',
 'New single-family floor plan at Coolidge Gateway Manor in Coolidge, AZ. Starting from published builder pricing. Specs as listed by the builder; square footage is approximate.',
 @type_sf, 'sale', 'available', 321990.00, 0, 'USD',
 'Arizona Boulevard & N Gateway Manor Place', 'Coolidge', 'Phoenix Metro', 'AZ', '85128', 'USA',
 4.0, 2.5, 2373, NULL, NULL, NULL, 0, @agent_marcus, CURDATE(),
 'century_communities', 'https://www.centurycommunities.com/find-your-new-home/arizona/phoenix-metro/coolidge/coolidge-gateway-manor/plans/troon/', 'troon'),
('cc-az-coolidge-gateway-ocotillo', 'CC-AZ-OCOTILLO', NULL, 'Ocotillo at Coolidge Gateway Manor',
 'New single-family floor plan at Coolidge Gateway Manor in Coolidge, AZ. Starting from published builder pricing. Specs as listed by the builder; square footage is approximate.',
 @type_sf, 'sale', 'available', 341990.00, 0, 'USD',
 'Arizona Boulevard & N Gateway Manor Place', 'Coolidge', 'Phoenix Metro', 'AZ', '85128', 'USA',
 5.0, 2.5, 2658, NULL, NULL, NULL, 0, @agent_eleanor, CURDATE(),
 'century_communities', 'https://www.centurycommunities.com/find-your-new-home/arizona/phoenix-metro/coolidge/coolidge-gateway-manor/plans/ocotillo/', 'ocotillo'),
-- CO
('cc-co-windler-monroe-21221', 'CC-CO-21221', NULL, 'The Monroe at Windler Boulevard I',
 'New single-family floor plan in the Boulevard I collection at Windler in Aurora, CO. Starting from published builder pricing. Specs as listed by the builder; square footage is approximate.',
 @type_sf, 'sale', 'available', 449990.00, 0, 'USD',
 '4728 N Ukraine Ct', 'Aurora', 'Denver Metro', 'CO', '80019', 'USA',
 3.0, 2.5, 1406, NULL, NULL, NULL, 1, @agent_julian, CURDATE(),
 'century_communities', 'https://www.centurycommunities.com/find-your-new-home/colorado/denver-metro/aurora/windler/windler-alley-21s/plans/21221/', '21221'),
('cc-co-windler-cambridge-21222', 'CC-CO-21222', NULL, 'The Cambridge at Windler Boulevard I',
 'New single-family floor plan in the Boulevard I collection at Windler in Aurora, CO. Starting from published builder pricing. Specs as listed by the builder; square footage is approximate.',
 @type_sf, 'sale', 'available', 454990.00, 0, 'USD',
 '4728 N Ukraine Ct', 'Aurora', 'Denver Metro', 'CO', '80019', 'USA',
 3.0, 2.5, 1444, NULL, NULL, NULL, 0, @agent_chloe, CURDATE(),
 'century_communities', 'https://www.centurycommunities.com/find-your-new-home/colorado/denver-metro/aurora/windler/windler-alley-21s/plans/21222/', '21222'),
('cc-co-windler-bellmont-21224', 'CC-CO-21224', NULL, 'The Bellmont at Windler Boulevard I',
 'New single-family floor plan in the Boulevard I collection at Windler in Aurora, CO. Starting from published builder pricing. Specs as listed by the builder; square footage is approximate.',
 @type_sf, 'sale', 'available', 464990.00, 0, 'USD',
 '4728 N Ukraine Ct', 'Aurora', 'Denver Metro', 'CO', '80019', 'USA',
 3.0, 2.5, 1537, NULL, NULL, NULL, 0, @agent_marcus, CURDATE(),
 'century_communities', 'https://www.centurycommunities.com/find-your-new-home/colorado/denver-metro/aurora/windler/windler-alley-21s/plans/21224/', '21224'),
('cc-co-windler-rosewood-21225', 'CC-CO-21225', NULL, 'The Rosewood at Windler Boulevard I',
 'New single-family floor plan in the Boulevard I collection at Windler in Aurora, CO. Starting from published builder pricing. Specs as listed by the builder; square footage is approximate.',
 @type_sf, 'sale', 'available', 474990.00, 0, 'USD',
 '4728 N Ukraine Ct', 'Aurora', 'Denver Metro', 'CO', '80019', 'USA',
 3.0, 2.5, 1613, NULL, NULL, NULL, 0, @agent_eleanor, CURDATE(),
 'century_communities', 'https://www.centurycommunities.com/find-your-new-home/colorado/denver-metro/aurora/windler/windler-alley-21s/plans/21225/', '21225'),
('cc-co-windler-winslow-21325', 'CC-CO-21325', NULL, 'The Winslow at Windler Boulevard I',
 'New single-family floor plan in the Boulevard I collection at Windler in Aurora, CO. Starting from published builder pricing. Specs as listed by the builder; square footage is approximate.',
 @type_sf, 'sale', 'available', 499990.00, 0, 'USD',
 '4728 N Ukraine Ct', 'Aurora', 'Denver Metro', 'CO', '80019', 'USA',
 4.0, 2.5, 1908, NULL, NULL, NULL, 0, @agent_julian, CURDATE(),
 'century_communities', 'https://www.centurycommunities.com/find-your-new-home/colorado/denver-metro/aurora/windler/windler-alley-21s/plans/21325/', '21325'),
-- TX
('cc-tx-stallion-run-dartford', 'CC-TX-DARTFORD', NULL, 'Dartford at Glen at Stallion Run',
 'New single-family floor plan at Glen at Stallion Run in Buda, TX. Starting from published builder pricing. Specs as listed by the builder; square footage is approximate.',
 @type_sf, 'sale', 'available', 314990.00, 0, 'USD',
 '6715 Smarty Jones Lane', 'Buda', 'Austin Metro', 'TX', '78610', 'USA',
 3.0, 2.0, 1567, NULL, NULL, NULL, 1, @agent_chloe, CURDATE(),
 'century_communities', 'https://www.centurycommunities.com/find-your-new-home/texas/austin-metro/buda/stallion-run/glen-at-stallion-run/plans/dartford/', 'dartford'),
('cc-tx-stallion-run-lexington', 'CC-TX-LEXINGTON', NULL, 'Lexington at Glen at Stallion Run',
 'New single-family floor plan at Glen at Stallion Run in Buda, TX. Starting from published builder pricing. Specs as listed by the builder; square footage is approximate.',
 @type_sf, 'sale', 'available', 316990.00, 0, 'USD',
 '6715 Smarty Jones Lane', 'Buda', 'Austin Metro', 'TX', '78610', 'USA',
 4.0, 2.5, 1785, NULL, NULL, NULL, 0, @agent_marcus, CURDATE(),
 'century_communities', 'https://www.centurycommunities.com/find-your-new-home/texas/austin-metro/buda/stallion-run/glen-at-stallion-run/plans/lexington/', 'lexington'),
('cc-tx-stallion-run-pinion', 'CC-TX-PINION', NULL, 'Pinion at Glen at Stallion Run',
 'New single-family floor plan at Glen at Stallion Run in Buda, TX. Starting from published builder pricing. Specs as listed by the builder; square footage is approximate.',
 @type_sf, 'sale', 'available', 324990.00, 0, 'USD',
 '6715 Smarty Jones Lane', 'Buda', 'Austin Metro', 'TX', '78610', 'USA',
 4.0, 2.0, 1853, NULL, NULL, NULL, 0, @agent_eleanor, CURDATE(),
 'century_communities', 'https://www.centurycommunities.com/find-your-new-home/texas/austin-metro/buda/stallion-run/glen-at-stallion-run/plans/pinion/', 'pinion'),
('cc-tx-stallion-run-laurel', 'CC-TX-LAUREL', NULL, 'Laurel at Glen at Stallion Run',
 'New single-family floor plan at Glen at Stallion Run in Buda, TX. Starting from published builder pricing. Specs as listed by the builder; square footage is approximate.',
 @type_sf, 'sale', 'available', 325990.00, 0, 'USD',
 '6715 Smarty Jones Lane', 'Buda', 'Austin Metro', 'TX', '78610', 'USA',
 3.0, 2.0, 1757, NULL, NULL, NULL, 0, @agent_julian, CURDATE(),
 'century_communities', 'https://www.centurycommunities.com/find-your-new-home/texas/austin-metro/buda/stallion-run/glen-at-stallion-run/plans/laurel/', 'laurel'),
('cc-tx-stallion-run-heron', 'CC-TX-HERON', NULL, 'Heron at Glen at Stallion Run',
 'New single-family floor plan at Glen at Stallion Run in Buda, TX. Starting from published builder pricing. Specs as listed by the builder; square footage is approximate.',
 @type_sf, 'sale', 'available', 327990.00, 0, 'USD',
 '6715 Smarty Jones Lane', 'Buda', 'Austin Metro', 'TX', '78610', 'USA',
 3.0, 2.0, 1841, NULL, NULL, NULL, 0, @agent_chloe, CURDATE(),
 'century_communities', 'https://www.centurycommunities.com/find-your-new-home/texas/austin-metro/buda/stallion-run/glen-at-stallion-run/plans/heron/', 'heron'),
-- FL
('cc-fl-grand-oaks-prescott', 'CC-FL-PRESCOTT', NULL, 'Prescott at Grand Oaks',
 'New single-family floor plan at Grand Oaks in Avon Park, FL. Starting from published builder pricing. Specs as listed by the builder; square footage is approximate.',
 @type_sf, 'sale', 'available', 250990.00, 0, 'USD',
 '604 Grand Oaks Drive', 'Avon Park', 'Central Florida', 'FL', '33825', 'USA',
 3.0, 2.0, 1477, NULL, NULL, NULL, 1, @agent_marcus, CURDATE(),
 'century_communities', 'https://www.centurycommunities.com/find-your-new-home/florida/central-florida/avon-park/grand-oaks/plans/prescott/', 'prescott'),
('cc-fl-grand-oaks-quail-ridge', 'CC-FL-QUAIL-RIDGE', NULL, 'Quail Ridge at Grand Oaks',
 'New single-family floor plan at Grand Oaks in Avon Park, FL. Starting from published builder pricing. Specs as listed by the builder; square footage is approximate.',
 @type_sf, 'sale', 'available', 260990.00, 0, 'USD',
 '604 Grand Oaks Drive', 'Avon Park', 'Central Florida', 'FL', '33825', 'USA',
 4.0, 2.0, 1666, NULL, NULL, NULL, 0, @agent_eleanor, CURDATE(),
 'century_communities', 'https://www.centurycommunities.com/find-your-new-home/florida/central-florida/avon-park/grand-oaks/plans/quail-ridge/', 'quail-ridge'),
('cc-fl-grand-oaks-coryell', 'CC-FL-CORYELL', NULL, 'Coryell at Grand Oaks',
 'New single-family floor plan at Grand Oaks in Avon Park, FL. Starting from published builder pricing. Specs as listed by the builder; square footage is approximate.',
 @type_sf, 'sale', 'available', 274990.00, 0, 'USD',
 '604 Grand Oaks Drive', 'Avon Park', 'Central Florida', 'FL', '33825', 'USA',
 3.0, 2.0, 1668, NULL, NULL, NULL, 0, @agent_julian, CURDATE(),
 'century_communities', 'https://www.centurycommunities.com/find-your-new-home/florida/central-florida/avon-park/grand-oaks/plans/coryell/', 'coryell'),
('cc-fl-grand-oaks-cambria', 'CC-FL-CAMBRIA', NULL, 'Cambria at Grand Oaks',
 'New single-family floor plan at Grand Oaks in Avon Park, FL. Starting from published builder pricing. Specs as listed by the builder; square footage is approximate.',
 @type_sf, 'sale', 'available', 290990.00, 0, 'USD',
 '604 Grand Oaks Drive', 'Avon Park', 'Central Florida', 'FL', '33825', 'USA',
 4.0, 3.0, 1941, NULL, NULL, NULL, 0, @agent_chloe, CURDATE(),
 'century_communities', 'https://www.centurycommunities.com/find-your-new-home/florida/central-florida/avon-park/grand-oaks/plans/cambria/', 'cambria'),
('cc-fl-grand-oaks-edinburg', 'CC-FL-EDINBURG', NULL, 'Edinburg at Grand Oaks',
 'New single-family floor plan at Grand Oaks in Avon Park, FL. Starting from published builder pricing. Specs as listed by the builder; square footage is approximate.',
 @type_sf, 'sale', 'available', 297990.00, 0, 'USD',
 '604 Grand Oaks Drive', 'Avon Park', 'Central Florida', 'FL', '33825', 'USA',
 3.0, 2.0, 1855, NULL, NULL, NULL, 0, @agent_marcus, CURDATE(),
 'century_communities', 'https://www.centurycommunities.com/find-your-new-home/florida/central-florida/avon-park/grand-oaks/plans/edinburg/', 'edinburg'),
-- GA
('cc-ga-garden-walk-auburn', 'CC-GA-AUBURN', NULL, 'Auburn at Garden Walk',
 'New single-family floor plan at Garden Walk in Jackson, GA. Starting from published builder pricing. Specs as listed by the builder; square footage is approximate.',
 @type_sf, 'sale', 'available', 258990.00, 0, 'USD',
 '133 Flowering Cherry Street', 'Jackson', 'Atlanta Metro', 'GA', '30233', 'USA',
 3.0, 2.5, 1566, NULL, NULL, NULL, 1, @agent_eleanor, CURDATE(),
 'century_communities', 'https://www.centurycommunities.com/find-your-new-home/georgia/atlanta-metro/jackson/garden-walk/plans/auburn/', 'auburn'),
('cc-ga-garden-walk-dupont', 'CC-GA-DUPONT', NULL, 'Dupont at Garden Walk',
 'New single-family floor plan at Garden Walk in Jackson, GA. Starting from published builder pricing. Specs as listed by the builder; square footage is approximate.',
 @type_sf, 'sale', 'available', 269990.00, 0, 'USD',
 '133 Flowering Cherry Street', 'Jackson', 'Atlanta Metro', 'GA', '30233', 'USA',
 4.0, 3.0, 1774, NULL, NULL, NULL, 0, @agent_julian, CURDATE(),
 'century_communities', 'https://www.centurycommunities.com/find-your-new-home/georgia/atlanta-metro/jackson/garden-walk/plans/dupont/', 'dupont'),
('cc-ga-garden-walk-essex', 'CC-GA-ESSEX', NULL, 'Essex at Garden Walk',
 'New single-family floor plan at Garden Walk in Jackson, GA. Starting from published builder pricing. Specs as listed by the builder; square footage is approximate.',
 @type_sf, 'sale', 'available', 279990.00, 0, 'USD',
 '133 Flowering Cherry Street', 'Jackson', 'Atlanta Metro', 'GA', '30233', 'USA',
 4.0, 2.5, 2014, NULL, NULL, NULL, 0, @agent_chloe, CURDATE(),
 'century_communities', 'https://www.centurycommunities.com/find-your-new-home/georgia/atlanta-metro/jackson/garden-walk/plans/essex/', 'essex'),
('cc-ga-garden-walk-gardner', 'CC-GA-GARDNER', NULL, 'Gardner at Garden Walk',
 'New single-family floor plan at Garden Walk in Jackson, GA. Starting from published builder pricing. Specs as listed by the builder; square footage is approximate.',
 @type_sf, 'sale', 'available', 294990.00, 0, 'USD',
 '133 Flowering Cherry Street', 'Jackson', 'Atlanta Metro', 'GA', '30233', 'USA',
 5.0, 3.0, 2180, NULL, NULL, NULL, 0, @agent_marcus, CURDATE(),
 'century_communities', 'https://www.centurycommunities.com/find-your-new-home/georgia/atlanta-metro/jackson/garden-walk/plans/gardner/', 'gardner'),
('cc-ga-twin-rivers-kingston', 'CC-GA-KINGSTON', NULL, 'Kingston at The Ridge at Twin Rivers',
 'New single-family floor plan at The Ridge at Twin Rivers in Covington, GA. Starting from published builder pricing. Specs as listed by the builder; square footage is approximate.',
 @type_sf, 'sale', 'available', 327990.00, 0, 'USD',
 '4585 Sunrise Ridge', 'Covington', 'Atlanta Metro', 'GA', '30016', 'USA',
 4.0, 2.5, 2376, NULL, NULL, NULL, 0, @agent_eleanor, CURDATE(),
 'century_communities', 'https://www.centurycommunities.com/find-your-new-home/georgia/atlanta-metro/covington/the-ridge-at-twin-rivers/plans/kingston/', 'kingston')
ON DUPLICATE KEY UPDATE
  `slug` = VALUES(`slug`),
  `title` = VALUES(`title`),
  `description` = VALUES(`description`),
  `property_type_id` = VALUES(`property_type_id`),
  `listing_purpose` = VALUES(`listing_purpose`),
  `status` = VALUES(`status`),
  `price` = VALUES(`price`),
  `price_on_request` = VALUES(`price_on_request`),
  `currency` = VALUES(`currency`),
  `address_line` = VALUES(`address_line`),
  `city` = VALUES(`city`),
  `region` = VALUES(`region`),
  `state` = VALUES(`state`),
  `postal_code` = VALUES(`postal_code`),
  `country` = VALUES(`country`),
  `bedrooms` = VALUES(`bedrooms`),
  `bathrooms` = VALUES(`bathrooms`),
  `sqft` = VALUES(`sqft`),
  `is_featured` = VALUES(`is_featured`),
  `agent_id` = VALUES(`agent_id`),
  `listed_at` = VALUES(`listed_at`),
  `source_name` = VALUES(`source_name`),
  `source_url` = VALUES(`source_url`),
  `source_reference` = VALUES(`source_reference`);

-- Replace images only for this import set
DELETE pi FROM `property_images` pi
INNER JOIN `properties` p ON p.id = pi.property_id
WHERE p.source_name = 'century_communities';

INSERT INTO `property_images` (`property_id`, `path`, `alt_text`, `caption`, `sort_order`, `is_cover`)
SELECT p.id, CONCAT('uploads/properties/', p.slug, '/cover.jpg'), p.title, NULL, 0, 1
FROM `properties` p WHERE p.source_name = 'century_communities'
UNION ALL
SELECT p.id, CONCAT('uploads/properties/', p.slug, '/01.jpg'), p.title, NULL, 10, 0
FROM `properties` p WHERE p.source_name = 'century_communities'
UNION ALL
SELECT p.id, CONCAT('uploads/properties/', p.slug, '/02.jpg'), p.title, NULL, 20, 0
FROM `properties` p WHERE p.source_name = 'century_communities'
UNION ALL
SELECT p.id, CONCAT('uploads/properties/', p.slug, '/03.jpg'), p.title, NULL, 30, 0
FROM `properties` p WHERE p.source_name = 'century_communities';

COMMIT;

-- Verification helpers (optional):
-- SELECT state, COUNT(*) FROM properties WHERE source_name='century_communities' GROUP BY state;
-- SELECT COUNT(*) FROM properties WHERE source_name='century_communities'; -- expect 25
