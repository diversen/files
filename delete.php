<?php

if (!session::checkAccessControl('files_allow_edit')){
    return;
}

moduleLoader::$referenceOptions = array ('edit_link' => 'true');
if (!moduleLoader::includeRefrenceModule()){   
    moduleLoader::$status['404'] = true;
    return;
}

$link = moduleLoader::$referenceLink;
$headline = lang::translate('files_delete_file') . MENU_SUB_SEPARATOR_SEC . $link;
headline_message($headline);

template::setTitle(lang::translate('files_add_file'));

$options = moduleLoader::getReferenceInfo();
files::setFileId($frag = 3);
$files = new files($options);
$files->viewFileFormDelete();
