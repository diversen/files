<?php

/**
 * controller file for doing downloads
 *
 * @package     module_system
 */

include_module('files');
$content_file = new contentFile(array());
$file = $content_file->getFile();
header("Content-type: $file[mimetype]");
echo $file['file'];
die;
