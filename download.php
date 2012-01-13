<?php

/**
 * controller file for doing downloads
 *
 * @package     module_system
 */

moduleLoader::includeModule('files');
$content_file = new files();
files::setFileId($frag = 2);
$file = $content_file->getFile();

header("Content-type: $file[mimetype]");
$data_len = strlen ($file['file']);

header ("Content-Length: $data_len");
echo $file['file'];
die;
