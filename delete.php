<?php

if (!session::checkAccessFromModuleIni('files_allow_edit')){
    return;
}

moduleloader::$referenceOptions = array ('edit_link' => 'true');
if (!moduleloader::includeRefrenceModule()){   
    moduleloader::$status['404'] = true;
    return;
}

$link = moduleloader::$referenceLink;
$options = moduleloader::getReferenceInfo();
$allow = conf::getModuleIni('files_allow_edit');

// if allow is set to user - this module only allow user to edit his own images
if ($allow == 'user') {
    //$table = moduleloader::moduleReferenceToTable($options['reference']);
    if (!user::ownID('files', $options['parent_id'], session::getUserId())) {
        moduleloader::setStatus(403);
        return;
    }   
}

$headline = lang::translate('Delete file') . MENU_SUB_SEPARATOR_SEC . $link;
html::headline($headline);

template::setTitle(lang::translate('Add file'));


files::setFileId($frag = 3);
$files = new files($options);
$files->viewFileFormDelete();
