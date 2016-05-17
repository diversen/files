<?php

namespace modules\files;

use diversen\conf;
use diversen\db;
use diversen\db\q;
use diversen\html;
use diversen\http;
use diversen\lang;
use diversen\layout;
use diversen\moduleloader;
use diversen\pagination;
use diversen\session;
use diversen\strings;
use diversen\template;
use diversen\upload\blob;
use diversen\uri;
use diversen\user;
use PDO;

/**
 * class content files is used for keeping track of file changes
 * in db. Uses object fileUpload
 *
 * @package files
 */
class module {


    public $errors = null;
    public $status = null;
    public $parent_id;
    public $maxsize = 2000000; // 2 mb max size
    public $options = array();
    public $path = '/files';
    public $fileTable = 'files';
    public $scaleWidth;
    public $allow;
    public $allowMime = array ();

    /**
     *
     * constructor sets init vars
     */
    function __construct($options = null){
         $this->options = $options;
         if (!isset($options['allow'])) {
            $this->allow = conf::getModuleIni('files_allow_edit');
         }
    }
    
        /**
     * Get uploaded files as a organized array
     * @return array $ary
     */
    public function getUploadedFilesArray () {
                
        $ary = array ();
        foreach ($_FILES['files']['name'] as $key => $name) {
            $ary[$key]['name'] = $name;
        }
        foreach ($_FILES['files']['type'] as $key => $type) {
            $ary[$key]['type'] = $type;
        }
        foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
            $ary[$key]['tmp_name'] = $tmp_name;
        }
        foreach ($_FILES['files']['error'] as $key => $error) {
            $ary[$key]['error'] = $error;
        }
        foreach ($_FILES['files']['size'] as $key => $size) {
            $ary[$key]['size'] = $size;
        }
        return $ary;
    }

    
    /**
     * get options from QUERY
     * @return array $options
     */
    public function getOptions() {
        $options = array
            ('parent_id' => $_GET['parent_id'],
            'return_url' => $_GET['return_url'],
            'reference' => $_GET['reference'],
            'query' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY)
        );
        return $options;
    }
    
    /**
     * check access to module based on options and blog ini settings 
     * @param array $options
     * @return void
     */
    public function checkAccess ($options) {
        
        // check access
        if (!session::checkAccessClean($this->allow)) {
            return false;
        }

        // if allow is set to user - this module only allow user to edit his filess
        // to references and parent_ids which he owns
        if ($this->allow == 'user') {
            $table = moduleloader::moduleReferenceToTable($options['reference']);
            if (!user::ownID($table, $options['parent_id'], session::getUserId())) {
                moduleloader::setStatus(403);
                return false;
            }
        }
        return true;
    }
    
    /**
     * set a headline and page title based on action
     * @param string $action 'add', 'edit', 'delete'
     */
    public function setHeadlineTitle ($action = '') {

        $options = $this->getOptions();
        if ($action == 'add') {
            $title = lang::translate('Add files');
        }
        
        if ($action == 'edit') {
            $title = lang::translate('Edit files');
        }
        
        if ($action == 'delete') {
            $title = lang::translate('Delete files');
        }
            
        // set headline and title
        $headline = $title . MENU_SUB_SEPARATOR_SEC;
        $headline.= html::createLink($options['return_url'], lang::translate('Go back'));

        echo html::getHeadline($headline);
        template::setTitle(lang::translate($title));
    }
    
    /**
     * add action
     * @return mixed
     */
    public function addAction() {
        
        if (!isset($_GET['parent_id'], $_GET['return_url'], $_GET['reference'] )) { 
            moduleloader::setStatus(403);
            return false;
        }
        
        // get options from QUERY
        $options = $this->getOptions();
        
        if (!$this->checkAccess($options)) {
            moduleloader::setStatus(403);
            return false;
        }

        layout::setMenuFromClassPath($options['reference']);
        
        $this->setHeadlineTitle('add');

        // display files module content
        $this->init($options);
        $this->viewFileFormInsert($options);
        
        // display files
        echo $this->displayFiles($options);
    }


    /**
     * delete action
     * @return type
     */
    public function deleteAction() {
        $options = $this->getOptions();
        if (!$this->checkAccess($options)) {
            moduleloader::setStatus(403);
            return;
        }

        layout::setMenuFromClassPath($options['reference']);
        $this->setHeadlineTitle('delete');
        $this->init($options);
        $this->viewFileFormDelete();
    }


    /**
     * edit action
     * @return void
     */
    public function editAction() {
        $options = $this->getOptions();
        
        // check access
        if (!$this->checkAccess($options)) {
            moduleloader::setStatus(403);
            return;
        } 
        
        layout::setMenuFromClassPath($options['reference']);
        $this->setHeadlineTitle('edit');

        $this->init($options);
        $this->viewFileFormUpdate($options);
    }

    /**
     * download controller
     */
    public function downloadAction() {
        
        $id = uri::fragment(2);
        $file = $this->getFile($id);

        if (empty($file)) {
            moduleloader::setStatus(404);
            return;
        }
        
        
        // Fine tuning of access can be set in image/config.php
        if (method_exists('modules\files\config', 'checkAccessDownload')) {
            
            $check = new \modules\files\config();
            $res = $check->checkAccessDownload($id);
            
            if (!$res) {
                header('HTTP/1.0 403 Forbidden');
                echo lang::translate('Access forbidden!');
                die();
            }
        }

        
        http::cacheHeaders();
        if (isset($file['mimetype']) && !empty($file['mimetype'])) {
            header("Content-type: $file[mimetype]");
        }
        echo $file['file'];
        die;
    
    }

    /**
     * ajaxhtml action (test)
     * @param type $url
     */
    public function ajaxhtmlAction($url) {
        $h = new html();
        echo $h->fileHtml5($url);
    }

    
    /**
     * admin action for checking user files uploads
     * @return boolean
     */
    public function adminAction () {
        if (!session::checkAccess('admin')) {
            return false;
        }
        
        layout::attachMenuItem('module', 
                array(
                    'title' => lang::translate('Images'), 
                    'url' => $this->path . '/admin'));
        
        $per_page = 10;
        $total = q::numRows('files')->fetch();
        $p = new pagination($total);

        $from = @$_GET['from'];
        if (isset($_GET['delete'])) {
            $this->deleteFile($_GET['delete']);    
            http::locationHeader($this->path . "/admin?from=$from", 
                    lang::translate('Image deleted'));
        }
        
        $rows = q::select('files', 'id, title, user_id')->
                order('created', 'DESC')->
                limit($p->from, $per_page)->
                fetch();
        
        echo "<table>";
        foreach ($rows as $row) {
            echo "<tr>";
            echo "<td>" . $this->getImgTag($row, 'file_thumb') . "</td>";
            echo "<td>"; 
            echo user::getAdminLink($row['user_id']);
            echo "<br />";
            echo user::getProfileLink($row['user_id']);
            echo "<br />";
            echo html::createLink($this->path . "/admin?delete=$row[id]&from=$from", lang::translate('Delete files'));
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>"; 
        echo $p->getPagerHTML();
    }
    

    /**
     * init
     * @param type $options
     */
    public function init ($options = null){
        $this->options = $options;
        $this->scaleWidth = conf::getModuleIni('files_scale_width');
        $this->path = '/files';
        $this->fileTable = 'files';
        $this->maxsize = conf::getModuleIni('files_max_size');
  
    }
    
    /**
     * check if files exists
     * @param int $id
     * @param string $reference
     * @return boolean
     */
    public function filesExists ($id, $reference) {
        return q::select('files', 'id')->
                filter('parent_id =', $id)->condition('AND')->
                filter('reference =', $reference)->
                fetchSingle();
    }

    
    /**
     * get a files html tag
     * @param array $row
     * @param string $size
     * @param array $options
     * @return string $html files tag
     */
    public function getImgTag ($row, $size = "file_org", $options = array ()) {
        return $img_tag = html::createHrefImage(
                $this->path . "/download/$row[id]/$row[title]?size=file_org", 
                $this->path . "/download/$row[id]/$row[title]?size=$size", 
                $options);

    }

   /**
    * method for creating a form for insert, update and deleting entries
    * in module_system module
    *
    *
    * @param string    method (update, delete or insert)
    * @param int       id (if delete or update)
    */
    public function viewFileForm($method, $id = null, $values = array(), $caption = null){
        
        html::$doUpload = true;
        $h = new html();
        
        $h->formStartAry(array('id' => 'files_upload_form'));
        if ($method == 'delete' && isset($id)) {
            $legend = lang::translate('Delete files');
            $h->legend($legend);
            $h->submit('submit', lang::translate('Delete'));
            echo $h->getStr();
            return;
        }
        
        $legend = '';
        
        // update
        if (isset($id)) {
            $values = $this->getSingleFileInfo($id);
            $h->init($values, 'submit'); 
            
            $legend = lang::translate('Edit files');
            $submit = lang::translate('Update');
            $this->options['multiple'] = false;
        } else {
            $h->init(html::specialEncode($_POST), 'submit'); 
            $legend = lang::translate('Add files');
            $submit = lang::translate('Add');

            
            
            
        }
        
        $h->legend($legend);

        $bytes = conf::getModuleIni('files_max_size');
        if (isset($this->options['multiple']) && $this->options['multiple'] == false) {
            unset($this->options['multiple']);
        } else {
            $this->options['multiple'] = "multiple";
        }
        
        if (!isset($id)) {
            $h->fileWithLabel('files[]', $bytes, $this->options);
        }
        

        $h->label('abstract', lang::translate('Abstract'));
        $h->textareaSmall('abstract');

        $h->submit('submit', $submit);
        $h->formEnd();
        echo $h->getStr();
    }
    
        /**
     * Note: All images are public
     * Expose images in JSON format
     * @return type
     */
    public function rpcAction () {

        // Check for sane options
        if (!isset($_GET['parent_id'], $_GET['reference'] )) { 
            return false;
        }
        
        $reference = $_GET['reference'];
        $parent_id = $_GET['parent_id'];
        
        // Fine tuning of access can be set in image/config.php
        if (method_exists('modules\files\config', 'checkAccess')) {
            $check = new \modules\files\config();
            if (!$check->checkAccess($parent_id)) {
                moduleloader::setStatus(403);
                return false;
            }
        }
        
        
        // Get rows
        $rows = $this->getAllFilesInfo(
                array(
                    'reference' => $reference, 
                    'parent_id' => $parent_id)
                );
        
        foreach ($rows as $key => $val) {
            $rows[$key]['url_m'] = "/files/download/$val[id]/" . strings::utf8SlugString($val['title']);
            $rows[$key]['url_s'] = "/files/download/$val[id]/" . strings::utf8SlugString($val['title']) . "?size=file_thumb";
            $str = strings::sanitizeUrlRigid(html::specialDecode($val['abstract']));
            $rows[$key]['title'] = $str; 
        }
        
        $files = array ('files' => $rows);
        echo json_encode($files);
        
        die;
    }
    
    /**
     * get full web path to a files.
     * @param type $row
     * @param type $size
     * @return string
     */
    public function getFullWebPath ($row, $size = null) {
        $str = $this->path . "/download/$row[id]/" . strings::utf8SlugString($row['title']);
        return $str;
    }
    
    /**
     * methoding for setting med size if allowed
     */
    public function getMedSize () {
        $med_size = 0;
        if (isset($_POST['scale_size']) && !empty($_POST['scale_size'])  && $_POST['scale_size'] > 0 ) {
            $med_size = (int)$_POST['scale_size']; 
            unset($_POST['scale_size']);
        }
        if (!$med_size) {
            $med_size = conf::getModuleIni('files_scale_width');
        }
        return $med_size;
    }

    /**
     * method for inserting a module into the database
     * (access control is cheched in controller file)
     *
     * @return boolean true on success or false on failure
     */
    public function insertFile ($file) {
        if (conf::getModuleIni('files_use_uniqid') == 1) {
            $options['uniqid'] = true;
        }

        $values = db::prepareToPost();
        $values['user_id'] = session::getUserId();
        $values['parent_id'] = $this->options['parent_id'];
        $values['reference'] = $this->options['reference'];
        
        $options['maxsize'] = $this->maxsize;

        $fp = blob::getFP($file, $options);
        if (!$fp) {
            $this->errors = blob::$errors;
            return false;
        }
        $values['file'] = $fp;

        if (isset($options['uniqid'])) {
            $values['title'] = md5(uniqid());
        } else {
            $values['title'] = $file['name'];
        }

        $values['mimetype'] = $file['type'];
        $bind = array('file' => PDO::PARAM_LOB);

        $db = new db();
        $res = $db->insert($this->fileTable, $values, $bind);
        return $res;
    }
    
    /**
     * method for inserting a module into the database
     * (access control is cheched in controller file)
     *
     * @return boolean true on success or false on failure
     */
    public function insertFiles ($input = 'files') {
        
        $_POST = html::specialDecode($_POST);
        
        $ary = $this->getUploadedFilesArray();
        foreach($ary as $file) {

            $res = $this->insertFile($file);
            if (!$res) {
                return false;
            }
        }

        return true;
    }

    /**
     *
     * @param type $files the files file to scale from
     * @param type $thumb the files file to scale to
     * @param type $width the x factor or width of the files
     * @return type 
     */
    public function scaleImage ($files, $thumb, $width){
        $res = filesscale::byX($files, $thumb, $width);
        if (!empty(filesscale::$errors)) { 
            $this->errors = filesscale::$errors;
        }
        return $res;
    }

    /**
     * validate before insert. No check for e.g. size
     * this is checked but no errors are given. 
     * just check if there is a file. 
     * @param type $mode 
     */
    public function validateInsert($mode = false){
        if ($mode != 'update') {
            if (empty($_FILES['files']['name']['0'])){
                $this->errors[] = lang::translate('No file was specified');
            }
        }
    }

    /**
     * method for delting a file
     *
     * @param   int     id of file
     * @return  boolean true on success and false on failure
     *
     */
    public function deleteFile($id){
        $res = q::delete($this->fileTable)->filter( 'id =', $id)->exec();
        return $res;
    }
    
    /**
     * delete all files based on parent and reference
     * @param int $parent
     * @param string $reference
     * @return boolean $res
     */
    public function deleteAll($parent, $reference) {
        $search = array('parent_id =' => $parent, 'reference =' => $reference);
        $res = q::delete($this->fileTable)->filterArray($search)->exec();
        return $res;
    }

    
    /**
     * get admin when operating as a sub module
     * @param array $options
     * @return string  
     */
    public static function subModuleAdminOption ($options){

        $i = new self();
        
        $url = $i->path . "/add?" . http_build_query($options);
        $extra = array ();
        if (isset($options['options'])) {
            $extra = $options['options'];
        }
        
        return html::createLink($url, lang::translate('Files'), $extra); 

    }


    /**
     * displays all files from db rows and options
     * @param array $rows
     * @param array $options
     * @return string $html
     */
    public function displayFiles($options){

        // get info about all filess
        $rows = $this->getAllFilesInfo($options);
        $str = "";
        foreach ($rows as $val){
            
            // generate title
            $title = lang::translate('Download');
            $title.= MENU_SUB_SEPARATOR_SEC;
            $title.= htmlspecialchars($val['title']);
            
            // create link to files
            $link_options = array('title' => htmlspecialchars($val['abstract']));
            $str.= html::createLink($val['files_url'], $title, $link_options);

            $add = $this->path . "/edit/$val[id]?" . $options['query'];
            $str.= MENU_SUB_SEPARATOR_SEC;
            $str.= html::createLink($add, lang::translate('Edit'));

            // delete link
            $delete = $this->path . "/delete/$val[id]?" . $options['query'];
            $str.= MENU_SUB_SEPARATOR;
            $str.= html::createLink($delete, lang::translate('Delete'));

            // break
            $str.= "<br />\n";
        }
        echo $str;
    }
    
        /**
     * displays all files from db rows and options
     * @param array $rows
     * @param array $options
     * @return string $html
     */
    public function displayFilesPublic($options){

        // get info about all filess
        $rows = $this->getAllFilesInfo($options);
        $str = '';
        foreach ($rows as $val){
            $title = htmlspecialchars($val['title']);
            
            // create link to files
            $link_options = array('title' => htmlspecialchars($val['abstract']));
            $str = '<i class="fa fa-download"></i>&nbsp;';
            $str.= html::createLink($val['files_url'], $title, $link_options);
            $str.= "<br />\n";
        }
        echo $str;
    }
    
    /**
     * get info about all files from array with parent_id and reference
     * @param array $options
     * @return array $rows array of rows
     */
    public function getAllFilesInfo($options){
        $db = new db();
        $search = array (
            'parent_id' => $options['parent_id'],
            'reference' => $options['reference']
        );

        $fields = array ('id', 'parent_id', 'title', 'abstract', 'published', 'created');
        $rows = $db->selectAll($this->fileTable, $fields, $search, null, null, 'created', false);
        foreach ($rows as $key => $row) {
            $rows[$key]['files_url'] = $this->getFullWebPath($row);
        } 
        
        return $rows;
    }

    /**
     * get info about a single files
     * @param int $id
     * @return array $row
     */
    public function getSingleFileInfo($id){

        $db = new db();
        $search = array (
            'id' => $id
        );

        $fields = array ('id', 'parent_id', 'title', 'abstract', 'published', 'created', 'reference', 'user_id');
        $row = $db->selectOne($this->fileTable, null, $search, $fields, null, 'created', false);
        return $row;
    }

    /**
     * method for fetching one full file row
     * @return array $row
     */
    public function getFile($id){
        $db = new db();

        
        $db->selectOne($this->fileTable, 'id', $id);
        $row = $db->selectOne($this->fileTable, 'id', $id);
        return $row;
    }

    /**
     * method for updating a module in database
     * @return boolean $res true on success or false on failure
     */
    public function updateFile() {

        $id = uri::fragment(2);
        $values = db::prepareToPost();
        
        $db = new db();
        $res = $db->update($this->fileTable, $values, $id);
        return $res;
    }

    /**
     * display a insert file form
     * @param type $options
     */
    public function viewFileFormInsertClean($options) {

        if (isset($options['redirect'])) {
            $redirect = $options['redirect'];
        } else {
            $redirect = '#!';
        }

        if (isset($_POST['submit'])){
            $this->validateInsert();
            if (!isset($this->errors)){
                $res = $this->insertFiles($options);
                if ($res){
                    session::setActionMessage(lang::translate('Image was added'));
                    http::locationHeader($redirect);
                } else {
                    html::errors($this->errors);
                }
            } else {
                html::errors($this->errors);
            }
        }
        $this->viewFileForm('insert');
    }
    
    /**
     * view form for uploading a file.
     * @param type $options
     */
    public function viewFileFormInsert($options){

        $redirect = $options['return_url'];
        if (isset($_POST['submit'])){
            $this->validateInsert();
            if (!isset($this->errors)){
                $res = $this->insertFiles();
                if ($res){
                    session::setActionMessage(lang::translate('Image was added'));
                    $this->redirectFilesMain($options);
                } else {
                    echo html::getErrors($this->errors);
                }
            } else {
                html::errors($this->errors);
            }
        }
        $this->viewFileForm('insert');
    }

    /**
     * view form for deleting files
     */
    public function viewFileFormDelete(){
        
        $id = uri::fragment(2);
        $options = $this->getOptions();
        if (isset($_POST['submit'])){
            if (!isset($this->errors)){
                $res = $this->deleteFile($id);
                if ($res){
                    session::setActionMessage(lang::translate('Image was deleted'));
                    $this->redirectFilesMain($options);
                }
            } else {
                html::errors($this->errors);
            }
        }
        $this->viewFileForm('delete', $id);
    }
    
    /**
     * Redrecit to main page
     * @param array $options
     */
    public function redirectFilesMain ($options) {
        $url = "/files/add/?$options[query]";
        http::locationHeader($url);   
    }

    /**
     * view form for updating an files
     */
    public function viewFileFormUpdate($options){
        $id = uri::fragment(2);
        if (isset($_POST['submit'])){
            $this->validateInsert('update');
            if (!isset($this->errors)){
                $res = $this->updateFile();
                if ($res){
                    session::setActionMessage(lang::translate('Image was updated'));
                    $this->redirectFilesMain($options);
                } else {
                    html::errors($this->errors);
                }
            } else {
                html::errors($this->errors);
            }
        }
        $this->viewFileForm('update', $id);
    }

}
