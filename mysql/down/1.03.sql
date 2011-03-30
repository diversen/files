ALTER TABLE `files` DROP COLUMN `reference`;

RENAME TABLE `files` TO `content_file`;