ALTER TABLE `files` DROP INDEX `reference_index`;

ALTER TABLE `files` DROP COLUMN `reference`;

RENAME TABLE `files` TO `content_file`;