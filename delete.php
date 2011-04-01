<?php

if (!session::checkAccessControl('files_allow_edit')){
    return;
}


if (!include_module ($_GET['reference'])){
    moduleLoader::$status['404'] = true;
    session::setActionMessage("No such module: $_GET[reference]");
    return;
}

$class = moduleLoader::modulePathToClassName($_GET['reference']);
$link = $class::getLinkFromId($_GET['parent_id']);

$headline = lang::translate('image_delete_file') . MENU_SUB_SEPARATOR_SEC . $link;
headline_message($headline);

$options = array (
    'redirect' => $_GET['return_url']);
$files = new files($options);

files::setFileId();
$files->viewFileFormDelete();