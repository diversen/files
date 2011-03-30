<?php

/**
 * view file for adding files
 *
 * @package    content
 */
if (!session::checkAccessControl('allow_edit_article')){
    return;
}

if (!include_module ($_GET['reference'])){
    moduleLoader::$status['404'] = true;
    session::setActionMessage("No such module: $_GET[reference]");
    return;
}

$class = moduleLoader::modulePathToClassName($_GET['reference']);
$link = $class::getLinkFromId($_GET['parent_id']);

$headline = lang::translate('Add File') . " :: " . $link;
headline_message($headline);

template::setTitle(lang::translate('Add File'));

$options = array (
    'parent_id' => $_GET['parent_id'],
    'reference' => $_GET['reference'],
    'redirect' => $_GET['return_url']);

$files = new files($options);
$files->viewFileFormInsert();
$files->displayAllFiles();