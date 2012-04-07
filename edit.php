<?php

if (!session::checkAccessControl('files_allow_edit')){
    return;
}

if (!moduleLoader::includeRefrenceModule()){   
    moduleLoader::$status['404'] = true;
    return;
}

moduleLoader::$referenceOptions = array ('type' => 'edit');

$link = moduleLoader::$referenceLink;
$headline = lang::translate('files_edit_file') . MENU_SUB_SEPARATOR_SEC . $link;
headline_message($headline);

template::setTitle(lang::translate('files_edit_file'));

$options = moduleLoader::getReferenceInfo();

files::setFileId($frag = 3);
$files = new files($options);
$files->viewFileFormUpdate();
