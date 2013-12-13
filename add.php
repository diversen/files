<?php

if (!session::checkAccessFromModuleIni('files_allow_edit')){
    return;
}

moduleloader::$referenceOptions = array ('edit_link' => 'true'); 
if (!moduleloader::includeRefrenceModule()){   
    moduleloader::$status['404'] = true;
    return;
}

// we now have a refrence module and a parent id wo work from.
$link = moduleloader::$referenceLink;

$options = moduleloader::getReferenceInfo();
$allow = config::getModuleIni('files_allow_edit');

// if allow is set to user - this module only allow user to edit his own images
if ($allow == 'user') {
    $table = moduleloader::moduleReferenceToTable($options['reference']);
    if (!user::ownID($table, $options['parent_id'], session::getUserId())) {
        moduleloader::setStatus(403);
        return;
    }   
}


$headline = lang::translate('Add file') . MENU_SUB_SEPARATOR_SEC . $link;
html::headline($headline);

template::setTitle(lang::translate('Add file'));


// set parent modules menu
layout::setMenuFromClassPath($options['reference']);

$files = new files($options);
$files->viewFileFormInsert();

$options['admin'] = true;
$rows = $files->getAllFilesInfo($options);
echo $files->displayFiles($rows, $options);
//print_r($rows);
