<?php

/**
 * controller file for doing downloads
 *
 * @package     module_system
 */

moduleloader::includeModule('files');
$content_file = new files();

if (conf::getModuleIni('files_use_uniqid') == 1) {
    $file = $content_file->getFileFromTitle(uri::fragment(3));
} else {
    files::setFileId($frag = 2);
    $file = $content_file->getFile();
}

if (empty($file)) {
    header("HTTP/1.1 404 Not Found");
    die;
}

header("Content-type: $file[mimetype]");
$data_len = strlen ($file['file']);

header ("Content-Length: $data_len");
echo $file['file'];
die;
