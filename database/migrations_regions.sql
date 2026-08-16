-- Regions taxonomy for staging DBs created before this feature.
-- Safe to re-run: creates table if missing, seeds defaults if empty.

CREATE TABLE IF NOT EXISTS `regions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(80) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `image_path` VARCHAR(500) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_regions_slug` (`slug`),
  KEY `idx_regions_active` (`is_active`, `sort_order`),
  KEY `idx_regions_featured` (`is_featured`, `is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `regions` (`slug`, `name`, `sort_order`, `is_active`, `is_featured`, `image_path`)
SELECT * FROM (
  SELECT 'aspen' AS slug, 'Aspen' AS name, 10 AS sort_order, 1 AS is_active, 1 AS is_featured, 'assets/img/collection-aspen.jpg' AS image_path
  UNION ALL SELECT 'vail', 'Vail', 20, 1, 1, 'assets/img/collection-vail.jpg'
  UNION ALL SELECT 'beaver-creek', 'Beaver Creek', 30, 1, 1, 'assets/img/collection-beaver-creek.jpg'
  UNION ALL SELECT 'telluride', 'Telluride', 40, 1, 0, NULL
  UNION ALL SELECT 'snowmass', 'Snowmass', 50, 1, 0, NULL
  UNION ALL SELECT 'steamboat', 'Steamboat', 60, 1, 0, NULL
  UNION ALL SELECT 'denver-metro', 'Denver Metro', 70, 1, 0, NULL
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM `regions` LIMIT 1);
