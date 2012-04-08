<?php

if (!session::checkAccessControl('files_allow_edit')){
    return;
}

moduleLoader::$referenceOptions = array ('edit_link' => 'true'); 
if (!moduleLoader::includeRefrenceModule()){   
    moduleLoader::$status['404'] = true;
    return;
}

// we now have a refrence module and a parent id wo work from.
$link = moduleLoader::$referenceLink;

$headline = lang::translate('files_add_file') . MENU_SUB_SEPARATOR_SEC . $link;
headline_message($headline);

template::setTitle(lang::translate('files_add_file'));
$options = moduleLoader::getReferenceInfo();

$files = new files($options);
$files->viewFileFormInsert();

$options['admin'] = true;
$rows = $files->getAllFilesInfo($options);
echo $files->displayFiles($rows, $options);
//print_r($rows);
