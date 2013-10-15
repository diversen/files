<?php

if (!session::checkAccessFromModuleIni('files_allow_edit')){
    return;
}

if (!moduleloader::includeRefrenceModule()){   
    moduleloader::$status['404'] = true;
    return;
}

moduleloader::$referenceOptions = array ('edit_link' => 'true');

$link = moduleloader::$referenceLink;
$headline = lang::translate('Edit file') . MENU_SUB_SEPARATOR_SEC . $link;
html::headline($headline);

template::setTitle(lang::translate('Edit file'));

$options = moduleloader::getReferenceInfo();

files::setFileId($frag = 3);
$files = new files($options);
$files->viewFileFormUpdate();
