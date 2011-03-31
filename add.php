<?php

/**
 * view file for adding files
 *
 * @package    content
 */
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

$headline = lang::translate('files_add_file') . " :: " . $link;
headline_message($headline);

template::setTitle(lang::translate('files_add_file'));

$options = array (
    'parent_id' => $_GET['parent_id'],
    'reference' => $_GET['reference'],
    'redirect' => $_GET['return_url']);

$files = new files($options);
$files->viewFileFormInsert();
$files->displayAllFiles();