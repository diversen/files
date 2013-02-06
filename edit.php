<?php

if (!session::checkAccessControl('files_allow_edit')){
    return;
}

if (!moduleloader::includeRefrenceModule()){   
    moduleloader::$status['404'] = true;
    return;
}

moduleloader::$referenceOptions = array ('edit_link' => 'true');

$link = moduleloader::$referenceLink;
$headline = lang::translate('files_edit_file') . MENU_SUB_SEPARATOR_SEC . $link;
headline_message($headline);

template::setTitle(lang::translate('files_edit_file'));

$options = moduleloader::getReferenceInfo();

files::setFileId($frag = 3);
$files = new files($options);
$files->viewFileFormUpdate();
