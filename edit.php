<?php

if (!session::checkAccessFromModuleIni('files_allow_edit')){
    return;
}

if (!moduleloader::includeRefrenceModule()){   
    moduleloader::$status['404'] = true;
    return;
}

moduleloader::$referenceOptions = array ('edit_link' => 'true');

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

$link = moduleloader::$referenceLink;
$headline = lang::translate('Edit file') . MENU_SUB_SEPARATOR_SEC . $link;
html::headline($headline);

template::setTitle(lang::translate('Edit file'));


files::setFileId($frag = 3);
$files = new files($options);
$files->viewFileFormUpdate();
