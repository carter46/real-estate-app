-- Set agent portrait paths (assets/img — committed with the app).
-- Run on staging after deploy if agents were seeded without photos.

UPDATE `agents` SET `photo_path` = 'assets/img/agent-eleanor-vance.jpg' WHERE `slug` = 'eleanor-vance';
UPDATE `agents` SET `photo_path` = 'assets/img/agent-julian-thorne.jpg' WHERE `slug` = 'julian-thorne';
UPDATE `agents` SET `photo_path` = 'assets/img/agent-chloe-sterling.jpg' WHERE `slug` = 'chloe-sterling';
UPDATE `agents` SET `photo_path` = 'assets/img/agent-marcus-wright.jpg' WHERE `slug` = 'marcus-wright';
