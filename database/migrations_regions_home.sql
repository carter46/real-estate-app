-- Homepage featured regions: image + featured flag.
-- Run once on staging after migrations_regions.sql.
-- If columns already exist, skip this file (or ignore duplicate-column errors).

ALTER TABLE `regions`
  ADD COLUMN `is_featured` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_active`,
  ADD COLUMN `image_path` VARCHAR(500) NULL DEFAULT NULL AFTER `is_featured`;

ALTER TABLE `regions`
  ADD KEY `idx_regions_featured` (`is_featured`, `is_active`, `sort_order`);

-- Seed the three classic homepage destinations (safe if rows exist).
UPDATE `regions`
SET `is_featured` = 1,
    `image_path` = 'assets/img/collection-aspen.jpg',
    `sort_order` = 10
WHERE `slug` = 'aspen';

UPDATE `regions`
SET `is_featured` = 1,
    `image_path` = 'assets/img/collection-vail.jpg',
    `sort_order` = 20
WHERE `slug` = 'vail';

UPDATE `regions`
SET `is_featured` = 1,
    `image_path` = 'assets/img/collection-beaver-creek.jpg',
    `sort_order` = 30
WHERE `slug` = 'beaver-creek';
