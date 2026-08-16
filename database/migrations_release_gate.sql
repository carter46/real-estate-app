-- Release-gate migration for existing databases.
-- Run once after pulling this release. Ignore "Duplicate column" errors if already applied.

ALTER TABLE `property_types`
  ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `sort_order`;

ALTER TABLE `amenities`
  ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `sort_order`;

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
  ('site_logo_path', NULL),
  ('site_favicon_path', NULL),
  ('mail_from_name', NULL)
ON DUPLICATE KEY UPDATE setting_key = setting_key;
