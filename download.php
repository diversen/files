<?php

/**
 * controller file for doing downloads
 *
 * @package     module_system
 */

include_module('files');
$content_file = new files();
files::setFileId($frag = 2);
$file = $content_file->getFile();

header("Content-type: $file[mimetype]");
echo $file['file'];
die;
