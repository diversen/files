<?php

if (!session::checkAccessControl('files_allow_edit')){
    return;
}

moduleloader::$referenceOptions = array ('edit_link' => 'true');
if (!moduleloader::includeRefrenceModule()){   
    moduleloader::$status['404'] = true;
    return;
}

$link = moduleloader::$referenceLink;
$headline = lang::translate('files_delete_file') . MENU_SUB_SEPARATOR_SEC . $link;
html::headline($headline);

template::setTitle(lang::translate('files_add_file'));

$options = moduleloader::getReferenceInfo();
files::setFileId($frag = 3);
$files = new files($options);
$files->viewFileFormDelete();
