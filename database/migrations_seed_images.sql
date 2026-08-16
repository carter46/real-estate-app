-- Sample listing images under uploads/properties/{slug}/ (same tree as admin uploads).
-- Run AFTER schema.sql + seed.sql on an existing staging DB.
-- Safe to re-run: clears prior image rows for these slugs, then re-inserts.

SET FOREIGN_KEY_CHECKS = 0;

DELETE pi FROM `property_images` pi
INNER JOIN `properties` p ON p.id = pi.property_id
WHERE p.slug IN (
  '450-red-mountain-rd',
  '1220-vail-valley-dr',
  '88-strawberry-park',
  '1240-red-mountain-road',
  '450-gore-creek-drive',
  'eagles-nest-ranch',
  'the-apex-at-snowmass',
  'the-glass-house-at-red-mountain'
);

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO `property_images` (`property_id`, `path`, `caption`, `sort_order`, `is_cover`)
SELECT p.id, 'uploads/properties/450-red-mountain-rd/cover.jpg', NULL, 0, 1
FROM `properties` p WHERE p.slug = '450-red-mountain-rd' LIMIT 1;

INSERT INTO `property_images` (`property_id`, `path`, `caption`, `sort_order`, `is_cover`)
SELECT p.id, 'uploads/properties/1220-vail-valley-dr/cover.jpg', NULL, 0, 1
FROM `properties` p WHERE p.slug = '1220-vail-valley-dr' LIMIT 1;

INSERT INTO `property_images` (`property_id`, `path`, `caption`, `sort_order`, `is_cover`)
SELECT p.id, 'uploads/properties/88-strawberry-park/cover.jpg', NULL, 0, 1
FROM `properties` p WHERE p.slug = '88-strawberry-park' LIMIT 1;

INSERT INTO `property_images` (`property_id`, `path`, `caption`, `sort_order`, `is_cover`)
SELECT p.id, 'uploads/properties/1240-red-mountain-road/cover.jpg', NULL, 0, 1
FROM `properties` p WHERE p.slug = '1240-red-mountain-road' LIMIT 1;

INSERT INTO `property_images` (`property_id`, `path`, `caption`, `sort_order`, `is_cover`)
SELECT p.id, 'uploads/properties/450-gore-creek-drive/cover.jpg', NULL, 0, 1
FROM `properties` p WHERE p.slug = '450-gore-creek-drive' LIMIT 1;

INSERT INTO `property_images` (`property_id`, `path`, `caption`, `sort_order`, `is_cover`)
SELECT p.id, 'uploads/properties/eagles-nest-ranch/cover.jpg', NULL, 0, 1
FROM `properties` p WHERE p.slug = 'eagles-nest-ranch' LIMIT 1;

INSERT INTO `property_images` (`property_id`, `path`, `caption`, `sort_order`, `is_cover`)
SELECT p.id, 'uploads/properties/the-apex-at-snowmass/cover.jpg', NULL, 0, 1
FROM `properties` p WHERE p.slug = 'the-apex-at-snowmass' LIMIT 1;

INSERT INTO `property_images` (`property_id`, `path`, `caption`, `sort_order`, `is_cover`)
SELECT p.id, 'uploads/properties/the-glass-house-at-red-mountain/cover.jpg', 'EXTERIOR', 0, 1
FROM `properties` p WHERE p.slug = 'the-glass-house-at-red-mountain' LIMIT 1;

INSERT INTO `property_images` (`property_id`, `path`, `caption`, `sort_order`, `is_cover`)
SELECT p.id, 'uploads/properties/the-glass-house-at-red-mountain/great-room.jpg', 'GREAT ROOM', 10, 0
FROM `properties` p WHERE p.slug = 'the-glass-house-at-red-mountain' LIMIT 1;

INSERT INTO `property_images` (`property_id`, `path`, `caption`, `sort_order`, `is_cover`)
SELECT p.id, 'uploads/properties/the-glass-house-at-red-mountain/master.jpg', 'PRIMARY SUITE', 20, 0
FROM `properties` p WHERE p.slug = 'the-glass-house-at-red-mountain' LIMIT 1;

INSERT INTO `property_images` (`property_id`, `path`, `caption`, `sort_order`, `is_cover`)
SELECT p.id, 'uploads/properties/the-glass-house-at-red-mountain/wine.jpg', 'WINE CELLAR', 30, 0
FROM `properties` p WHERE p.slug = 'the-glass-house-at-red-mountain' LIMIT 1;
