-- Real Estate Application Schema
-- MySQL 8.0+ / MariaDB 10.4+ recommended
-- Reproducible on a clean database. No seeded passwords.
--
-- Shared hosting (phpMyAdmin / Hostinger):
--   1. Create the database in the hosting panel (you cannot CREATE DATABASE here).
--   2. Open that database in phpMyAdmin (select it in the left sidebar).
--   3. Import this file, then seed.sql.
-- Local MySQL with full privileges: create/select the DB first, then import.

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `property_amenity`;
DROP TABLE IF EXISTS `property_images`;
DROP TABLE IF EXISTS `inquiries`;
DROP TABLE IF EXISTS `properties`;
DROP TABLE IF EXISTS `amenities`;
DROP TABLE IF EXISTS `property_types`;
DROP TABLE IF EXISTS `regions`;
DROP TABLE IF EXISTS `agents`;
DROP TABLE IF EXISTS `offices`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------------
-- Users (admins). Password is created via one-time setup (Phase 2), never seeded.
-- ---------------------------------------------------------------------------
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(191) NOT NULL,
  `password_hash` VARCHAR(255) NULL DEFAULT NULL COMMENT 'NULL until one-time admin setup completes',
  `name` VARCHAR(120) NOT NULL DEFAULT '',
  `role` ENUM('admin') NOT NULL DEFAULT 'admin',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `setup_completed_at` DATETIME NULL DEFAULT NULL,
  `last_login_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Agents (public experts / listing agents)
-- ---------------------------------------------------------------------------
CREATE TABLE `agents` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(160) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `title` VARCHAR(160) NOT NULL DEFAULT '',
  `region` VARCHAR(120) NOT NULL DEFAULT '',
  `bio` TEXT NULL,
  `photo_path` VARCHAR(500) NULL DEFAULT NULL,
  `badge` VARCHAR(80) NULL DEFAULT NULL,
  `email` VARCHAR(191) NULL DEFAULT NULL,
  `phone` VARCHAR(40) NULL DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_agents_slug` (`slug`),
  KEY `idx_agents_active_sort` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Property types
-- ---------------------------------------------------------------------------
CREATE TABLE `property_types` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(80) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_property_types_slug` (`slug`),
  KEY `idx_property_types_active` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Regions (destinations used on listings / filters)
-- ---------------------------------------------------------------------------
CREATE TABLE `regions` (
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

-- ---------------------------------------------------------------------------
-- Properties (single source of truth)
-- ---------------------------------------------------------------------------
CREATE TABLE `properties` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(191) NOT NULL,
  `reference_code` VARCHAR(64) NOT NULL,
  `mls_number` VARCHAR(64) NULL DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` MEDIUMTEXT NULL,
  `property_type_id` INT UNSIGNED NULL DEFAULT NULL,
  `listing_purpose` ENUM('sale', 'rent', 'lease') NOT NULL DEFAULT 'sale',
  `status` ENUM(
    'draft',
    'available',
    'pending',
    'under_contract',
    'sold',
    'private',
    'archived'
  ) NOT NULL DEFAULT 'draft',
  `price` DECIMAL(15, 2) NULL DEFAULT NULL,
  `price_on_request` TINYINT(1) NOT NULL DEFAULT 0,
  `currency` CHAR(3) NOT NULL DEFAULT 'USD',
  `address_line` VARCHAR(255) NOT NULL DEFAULT '',
  `city` VARCHAR(120) NOT NULL DEFAULT '',
  `region` VARCHAR(120) NOT NULL DEFAULT '',
  `state` VARCHAR(80) NOT NULL DEFAULT 'CO',
  `postal_code` VARCHAR(20) NOT NULL DEFAULT '',
  `country` VARCHAR(80) NOT NULL DEFAULT 'USA',
  `bedrooms` DECIMAL(4, 1) NULL DEFAULT NULL,
  `bathrooms` DECIMAL(4, 1) NULL DEFAULT NULL,
  `sqft` INT UNSIGNED NULL DEFAULT NULL,
  `lot_acres` DECIMAL(10, 2) NULL DEFAULT NULL,
  `year_built` SMALLINT UNSIGNED NULL DEFAULT NULL,
  `badge` VARCHAR(80) NULL DEFAULT NULL COMMENT 'e.g. Just Listed, Exclusive Listing',
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `agent_id` INT UNSIGNED NULL DEFAULT NULL,
  `agent_quote` TEXT NULL DEFAULT NULL COMMENT 'Per-listing quote on property detail sticky agent card',
  `listed_at` DATE NULL DEFAULT NULL,
  `source_name` VARCHAR(80) NULL DEFAULT NULL COMMENT 'Internal import source e.g. century_communities',
  `source_url` VARCHAR(500) NULL DEFAULT NULL COMMENT 'Parent listing URL (admin/dev only)',
  `source_reference` VARCHAR(120) NULL DEFAULT NULL COMMENT 'Stable source key e.g. plan slug',
  `created_by` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_properties_slug` (`slug`),
  UNIQUE KEY `uq_properties_reference_code` (`reference_code`),
  UNIQUE KEY `uq_properties_mls_number` (`mls_number`),
  KEY `idx_properties_status` (`status`),
  KEY `idx_properties_region` (`region`),
  KEY `idx_properties_price` (`price`),
  KEY `idx_properties_beds` (`bedrooms`),
  KEY `idx_properties_featured` (`is_featured`),
  KEY `idx_properties_created` (`created_at`),
  KEY `idx_properties_city` (`city`),
  KEY `idx_properties_address_city` (`address_line`(100), `city`),
  KEY `idx_properties_source` (`source_name`, `source_reference`),
  CONSTRAINT `fk_properties_type`
    FOREIGN KEY (`property_type_id`) REFERENCES `property_types` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_properties_agent`
    FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_properties_created_by`
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Property images (ordered gallery; one cover enforced in application logic — Phase 3)
-- Note: store mls_number as NULL (not '') to avoid UNIQUE collisions on empty strings.
-- ---------------------------------------------------------------------------
CREATE TABLE `property_images` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `property_id` INT UNSIGNED NOT NULL,
  `path` VARCHAR(500) NOT NULL COMMENT 'Relative upload path under uploads/ (or absolute https URL if used)',
  `alt_text` VARCHAR(255) NULL DEFAULT NULL,
  `caption` VARCHAR(160) NULL DEFAULT NULL COMMENT 'Room/label overlay e.g. GREAT ROOM (Glass House gallery)',
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_cover` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_property_images_property` (`property_id`),
  KEY `idx_property_images_cover` (`property_id`, `is_cover`),
  KEY `idx_property_images_sort` (`property_id`, `sort_order`),
  CONSTRAINT `fk_property_images_property`
    FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Amenities + pivot
-- ---------------------------------------------------------------------------
CREATE TABLE `amenities` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(80) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `category` ENUM('interior', 'exterior', 'community', 'other') NOT NULL DEFAULT 'other',
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_amenities_slug` (`slug`),
  KEY `idx_amenities_active` (`is_active`, `category`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `property_amenity` (
  `property_id` INT UNSIGNED NOT NULL,
  `amenity_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`property_id`, `amenity_id`),
  CONSTRAINT `fk_property_amenity_property`
    FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_property_amenity_amenity`
    FOREIGN KEY (`amenity_id`) REFERENCES `amenities` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Inquiries (contact + property)
-- ---------------------------------------------------------------------------
CREATE TABLE `inquiries` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` ENUM('contact', 'property_inquiry') NOT NULL DEFAULT 'contact',
  `status` ENUM('new', 'in_progress', 'resolved') NOT NULL DEFAULT 'new',
  `property_id` INT UNSIGNED NULL DEFAULT NULL,
  `first_name` VARCHAR(80) NOT NULL DEFAULT '',
  `last_name` VARCHAR(80) NOT NULL DEFAULT '',
  `email` VARCHAR(191) NOT NULL,
  `phone` VARCHAR(40) NULL DEFAULT NULL,
  `interest` VARCHAR(120) NULL DEFAULT NULL,
  `message` TEXT NOT NULL,
  `admin_notes` TEXT NULL,
  `assigned_to` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_inquiries_status` (`status`),
  KEY `idx_inquiries_type` (`type`),
  KEY `idx_inquiries_created` (`created_at`),
  CONSTRAINT `fk_inquiries_property`
    FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_inquiries_assigned`
    FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Offices (contact page)
-- ---------------------------------------------------------------------------
CREATE TABLE `offices` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(160) NOT NULL,
  `address_line` VARCHAR(255) NOT NULL DEFAULT '',
  `city` VARCHAR(120) NOT NULL DEFAULT '',
  `region` VARCHAR(120) NOT NULL DEFAULT '',
  `postal_code` VARCHAR(20) NOT NULL DEFAULT '',
  `phone` VARCHAR(40) NULL DEFAULT NULL,
  `email` VARCHAR(191) NULL DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Settings (key/value)
-- ---------------------------------------------------------------------------
CREATE TABLE `settings` (
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
