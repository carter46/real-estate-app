-- Safe seed data — NO admin passwords.
-- Import after schema.sql.
-- Admin account is created via one-time setup in Phase 2 (admin/setup.php).

USE `real_estate`;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `property_amenity`;
TRUNCATE TABLE `property_images`;
TRUNCATE TABLE `inquiries`;
TRUNCATE TABLE `properties`;
TRUNCATE TABLE `amenities`;
TRUNCATE TABLE `property_types`;
TRUNCATE TABLE `agents`;
TRUNCATE TABLE `offices`;
TRUNCATE TABLE `settings`;
-- users intentionally left empty (no password seed)
SET FOREIGN_KEY_CHECKS = 1;

-- Property types (from reference add form + filters)
INSERT INTO `property_types` (`slug`, `name`, `sort_order`) VALUES
  ('estate', 'Estate', 10),
  ('condo', 'Luxury Condo', 20),
  ('ranch', 'Ranch', 30),
  ('chalet', 'Chalet', 40),
  ('single_family', 'Single Family', 50),
  ('land', 'Land', 60);

-- Amenities (from reference add form)
INSERT INTO `amenities` (`slug`, `name`, `category`, `sort_order`) VALUES
  ('smart-home', 'Smart Home Tech', 'interior', 10),
  ('wine-cellar', 'Wine Cellar', 'interior', 20),
  ('home-theater', 'Home Theater', 'interior', 30),
  ('ski-in-ski-out', 'Ski-In/Ski-Out', 'exterior', 40),
  ('heated-driveway', 'Heated Driveway', 'exterior', 50),
  ('outdoor-kitchen', 'Outdoor Kitchen', 'exterior', 60),
  ('gated-access', 'Gated Access', 'community', 70),
  ('clubhouse', 'Clubhouse', 'community', 80);

-- Agents (from our_experts reference — unique rows)
INSERT INTO `agents` (`slug`, `name`, `title`, `region`, `bio`, `badge`, `sort_order`, `is_active`) VALUES
  ('eleanor-vance', 'Eleanor Vance', 'Managing Broker', 'Aspen Core',
   'With over two decades navigating Aspen''s complex luxury market, Eleanor provides unmatched strategic advisory for ultra-high-net-worth clientele seeking legacy properties.',
   'Top Producer', 10, 1),
  ('julian-thorne', 'Julian Thorne', 'Global Luxury Specialist', 'Vail',
   'Specializing in ski-in/ski-out estates and private ranches. Julian''s international network connects global buyers with Colorado''s most exclusive alpine retreats.',
   NULL, 20, 1),
  ('chloe-sterling', 'Chloe Sterling', 'Principal Agent', 'Denver Metro',
   'Chloe brings a data-driven approach to Denver''s high-end urban market, focusing on luxury penthouses and historic Cherry Creek estates with unparalleled precision.',
   NULL, 30, 1),
  ('marcus-wright', 'Marcus Wright', 'Ranch & Land Director', 'Telluride',
   'The definitive authority on large-scale Colorado land acquisitions. Marcus specializes in sporting properties, equestrian estates, and conservation easements.',
   NULL, 40, 1);

-- Offices (from contact/footer reference)
INSERT INTO `offices` (`name`, `address_line`, `city`, `region`, `postal_code`, `sort_order`, `is_active`) VALUES
  ('Vail Village', '', 'Vail', 'Vail Valley', '', 10, 1),
  ('Beaver Creek', '', 'Beaver Creek', 'Vail Valley', '', 20, 1),
  ('Aspen Core', '', 'Aspen', 'Roaring Fork', '', 30, 1),
  ('Denver Cherry Creek', '', 'Denver', 'Denver Metro', '', 40, 1);

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
  ('site_phone', '800.555.0123'),
  ('site_email', 'info@example.com'),
  ('site_name', 'Sunview Development and Consultancy (SDC)'),
  ('site_logo_path', NULL),
  ('site_favicon_path', NULL),
  ('mail_from_name', NULL);

-- NOTE: Admin setup is detected ONLY from the users table (no settings flag).
-- Never place plaintext or known default passwords in this file.

-- Curated unique public properties (one row each; used across home/listings/detail)
-- Gallery rows omitted in seed (add images via admin uploads).
-- ADMIN SETUP: visit admin/setup.php once to create the first admin password (never seeded).

SET @type_estate := (SELECT id FROM property_types WHERE slug = 'estate' LIMIT 1);
SET @type_chalet := (SELECT id FROM property_types WHERE slug = 'chalet' LIMIT 1);
SET @type_ranch  := (SELECT id FROM property_types WHERE slug = 'ranch' LIMIT 1);
SET @agent_eleanor := (SELECT id FROM agents WHERE slug = 'eleanor-vance' LIMIT 1);
SET @agent_julian  := (SELECT id FROM agents WHERE slug = 'julian-thorne' LIMIT 1);
SET @agent_marcus  := (SELECT id FROM agents WHERE slug = 'marcus-wright' LIMIT 1);

INSERT INTO `properties` (
  `slug`, `reference_code`, `title`, `description`, `property_type_id`, `listing_purpose`,
  `status`, `price`, `price_on_request`, `currency`,
  `address_line`, `city`, `region`, `state`, `postal_code`, `country`,
  `bedrooms`, `bathrooms`, `sqft`, `lot_acres`, `badge`, `is_featured`,
  `agent_id`, `listed_at`
) VALUES
(
  '450-red-mountain-rd',
  'REF-450-RMR',
  '450 Red Mountain Rd',
  'Featured Aspen estate on Red Mountain.',
  @type_estate,
  'sale', 'available', 18500000.00, 0, 'USD',
  '450 Red Mountain Rd', 'Aspen', 'Aspen', 'CO', '81611', 'USA',
  6.0, 8.0, 8200, NULL, 'Just Listed', 1,
  @agent_eleanor, CURDATE()
),
(
  '1220-vail-valley-dr',
  'REF-1220-VVD',
  '1220 Vail Valley Dr',
  'Luxury residence in Vail Valley.',
  @type_estate,
  'sale', 'available', 12950000.00, 0, 'USD',
  '1220 Vail Valley Dr', 'Vail', 'Vail', 'CO', '81657', 'USA',
  5.0, 6.0, 6500, NULL, NULL, 1,
  @agent_julian, CURDATE()
),
(
  '88-strawberry-park',
  'REF-88-SP',
  '88 Strawberry Park',
  'Beaver Creek mountain retreat.',
  @type_chalet,
  'sale', 'available', 9850000.00, 0, 'USD',
  '88 Strawberry Park', 'Beaver Creek', 'Vail', 'CO', '81620', 'USA',
  4.0, 5.5, 5200, NULL, NULL, 1,
  @agent_julian, CURDATE()
),
(
  '1240-red-mountain-road',
  'REF-1240-RMR',
  '1240 Red Mountain Road',
  'Exclusive Aspen listing on Red Mountain Road.',
  @type_estate,
  'sale', 'available', 14500000.00, 0, 'USD',
  '1240 Red Mountain Road', 'Aspen', 'Aspen', 'CO', '81611', 'USA',
  6.0, 8.0, 8240, NULL, 'Exclusive Listing', 0,
  @agent_eleanor, CURDATE()
),
(
  '450-gore-creek-drive',
  'REF-450-GCD',
  '450 Gore Creek Drive',
  'Just listed residence on Gore Creek Drive, Vail.',
  @type_chalet,
  'sale', 'available', 9250000.00, 0, 'USD',
  '450 Gore Creek Drive', 'Vail', 'Vail', 'CO', '81657', 'USA',
  4.0, 5.5, 4100, NULL, 'Just Listed', 0,
  @agent_julian, CURDATE()
),
(
  'eagles-nest-ranch',
  'REF-ENR-TEL',
  'Eagle''s Nest Ranch',
  'Expansive ranch estate in Telluride.',
  @type_ranch,
  'sale', 'available', 22000000.00, 0, 'USD',
  'Eagle''s Nest Ranch', 'Telluride', 'Telluride', 'CO', '81435', 'USA',
  8.0, 10.0, 12500, NULL, NULL, 0,
  @agent_marcus, CURDATE()
),
(
  'the-apex-at-snowmass',
  'REF-APEX-SM',
  'The Apex at Snowmass',
  'Signature property in Snowmass Village spanning 4.5 acres.',
  @type_estate,
  'sale', 'available', 35500000.00, 0, 'USD',
  'The Apex at Snowmass', 'Snowmass Village', 'Aspen', 'CO', '81615', 'USA',
  7.0, 9.0, 15200, 4.50, 'Signature Property', 0,
  @agent_eleanor, CURDATE()
),
(
  'the-glass-house-at-red-mountain',
  'REF-GLASS-RM',
  'The Glass House at Red Mountain',
  'Architectural glass estate on Red Mountain in Aspen. Chef''s kitchen, wine room, smart home systems, infinity pool and spa, heated terraces, and geothermal snow-melt driveway.',
  @type_estate,
  'sale', 'available', 32500000.00, 0, 'USD',
  'The Glass House at Red Mountain', 'Aspen', 'Aspen', 'CO', '81611', 'USA',
  6.0, 8.0, 12000, 5.40, NULL, 0,
  @agent_eleanor, CURDATE()
);

-- Sample amenities for Glass House
INSERT INTO `property_amenity` (`property_id`, `amenity_id`)
SELECT p.id, a.id
FROM `properties` p
CROSS JOIN `amenities` a
WHERE p.slug = 'the-glass-house-at-red-mountain'
  AND a.slug IN ('smart-home', 'wine-cellar', 'heated-driveway', 'gated-access');

-- Detail-page agent quote slot (Glass House template)
UPDATE `properties`
SET `agent_quote` = 'This residence represents the pinnacle of Red Mountain architecture — light, scale, and privacy in equal measure.'
WHERE `slug` = 'the-glass-house-at-red-mountain';

-- ---------------------------------------------------------------------------
-- ADMIN SETUP NOTE
-- ---------------------------------------------------------------------------
-- No rows are inserted into `users`.
-- Visit admin/setup.php once to create the first admin:
--   password_hash() / PASSWORD_DEFAULT; sets users.setup_completed_at.
-- Never place plaintext or known default passwords in this file.
