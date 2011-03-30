<?php

/**
 * view file for adding files
 *
 * @package    content
 */
if (!session::checkAccessControl('allow_edit_article')){
    return;
}

include_module ('content/article');
include_module ('files');

$article = new article();
$article_row = $article->getArticle();

// set headline message.
$link = $article->getArticleHTMLLink();
$headline = lang::translate('Edit File') . " :: " . $link;
headline_message($headline);

$title = $article->getArticleTitle();
$_TEMPLATE_ASSIGN = array('title' => $title);

$redirect = $article->getArticleUrl(article::$id);
$options = array (
    'reference' => 'article',
    'redirect' => $redirect);

$content_file = new contentFile($options);
$content_file->viewFileFormUpdate();