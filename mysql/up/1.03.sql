RENAME TABLE `content_file` TO `files`;

ALTER TABLE `files` ADD `reference` varchar(255) NOT NULL;

CREATE INDEX `reference_index` ON `files` (reference);