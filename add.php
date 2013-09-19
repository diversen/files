<?php

if (!session::checkAccessControl('files_allow_edit')){
    return;
}

moduleloader::$referenceOptions = array ('edit_link' => 'true'); 
if (!moduleloader::includeRefrenceModule()){   
    moduleloader::$status['404'] = true;
    return;
}

// we now have a refrence module and a parent id wo work from.
$link = moduleloader::$referenceLink;

$headline = lang::translate('Add file') . MENU_SUB_SEPARATOR_SEC . $link;
html::headline($headline);

template::setTitle(lang::translate('Add file'));
$options = moduleloader::getReferenceInfo();

// set parent modules menu
layout::setMenuFromClassPath($options['reference']);

$files = new files($options);
$files->viewFileFormInsert();

$options['admin'] = true;
$rows = $files->getAllFilesInfo($options);
echo $files->displayFiles($rows, $options);
//print_r($rows);
