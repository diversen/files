<?php

/**
 * view file for deleting files
 *
 * @package    content
 */
if (!session::checkAccessControl('allow_edit_article')){
    return;
}
include_model('content/article');
$article = new article();

// set headline message.
$link = $article->getArticleHTMLLink();
$headline = lang::translate('Delete File') . " :: " . $link;
headline_message($headline);

include_module ('files');

$redirect = $article->getArticleUrl(article::$id);
$options = array (
    'reference' => 'article',
    'redirect' => $redirect);

$content_file = new contentFile($options);
$file = $content_file->getFile();
$title = lang::translate('Delete File') . ' :: ' . $file['title'];

$content_file->viewFileFormDelete();
$content_file->displayAllFiles();