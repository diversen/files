<?php

use diversen\upload\blob as upload_blob;
use diversen\db\q as db_q;

/**
 * model file for doing file uploads
 *
 * @package     content
 */

/**
 * @ignore
 */
//include_once "coslib/upload.php";

/**
 * class content files is used for keeping track of file changes
 * in db. Uses object fileUpload
 */
class files {

    public static $errors = null;
    public static $status = null;
    public static $parent_id;
    public static $fileId;
    public static $maxsize = 2000000; // 2 mb max size
    public static $options = array();
    public static $fileTable = 'files';

    /**
     * constructor sets init vars
     * @param array $options['parent_id'] 
     *                  parent_id. Connect your file to a id
     *              $options['reference'] 
     *                  reference. Connect your file to a reference, e.g. a module_name
     *              $options['maxsize'] maxsize. 
     *                  Set maxsize of uploaded file. Else we will use ini setting
     *                  Set in bytes
     *                                          
     */
    function __construct($options = null){
        self::$options = $options;
        
        if (!isset($options['maxsize'])) {
            $maxsize = conf::getModuleIni('files_max_size');
            if ($maxsize) {
                self::$options['maxsize'] = $maxsize;
            }
        }
    }

    public static function setFileId ($frag){
        self::$fileId = uri::$fragments[$frag];
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
     
        $values = html::specialEncode($values);
        html::formStart('file_upload_form');
        if ($method == 'delete' && isset($id)) {
            $legend = lang::translate('Delete file');
            html::legend($legend);
            html::submit('submit', lang::translate('Delete'));
            html::formEnd();
            echo html::getStr();
            return;
        }
        
        $legend = '';
        if (isset($id)) {
            $values = self::getSingleFileInfo($id);
            html::init($values, 'submit'); 
            $legend = lang::translate('Edit file');
            $submit = lang::system('system_submit_update');
        } else {
            $legend = lang::translate('Add file');
            $submit = lang::system('system_submit_add');
        }
        
        html::legend($legend);
        html::label('abstract', lang::translate('Abstract'));
        html::textareaSmall('abstract');
        
        $bytes = conf::getModuleIni('files_max_size');
        html::fileWithLabel('file', $bytes);
        
        html::submit('submit', $submit);
        html::formEnd();
        echo html::getStr();
        return;
    }
    
    /**
     * method for inserting a module into the database
     * (access control is cheched in controller file)
     *
     * @return boolean true on success or false on failure
     */
    public function insertFile ($options = array ()) {
        
        if (conf::getModuleIni('files_use_uniqid') == 1) {
            $options['uniqid'] = true;
        }
        
        $values = db::prepareToPost();
        $values['user_id'] = session::getUserId();
        $values['parent_id'] = self::$options['parent_id'];
        $values['reference'] = self::$options['reference'];
        $options['maxsize'] = self::$options['maxsize'];
        
        $fp = upload_blob::getFP('file', $options);
        if (!$fp) {
            self::$errors = upload_blob::$errors;
            return false;
        }
        $values['file'] = $fp;
        
        if (isset($options['uniqid'])) {
            $values['title'] = md5(uniqid());
        } else {
            $values['title'] = $_FILES['file']['name'];
        }
        
        $values['mimetype'] = $_FILES['file']['type'];
        $bind = array('file' => PDO::PARAM_LOB);
        
        $db = new db();
        $res = $db->insert(self::$fileTable, $values, $bind);
        return $res;
    }

    /**
     * method for validating a post before insert
     */
    public function validateInsert($mode = false){
        if (empty($_FILES['file']['name'])){
            self::$errors[] = lang::translate('No file was specified');
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
        $db = new db();
        $res = $db->delete(self::$fileTable, 'id', $id);
        return $res;
    }
    
    /**
     * 
     * @param type $options array ('parent_id', 'reference');
     * @return boolean $res true on succes and false on failure
     */
    public function deleteAllFiles ($options) {
        $files = $this->getAllFilesInfo($options);

        $final = true;
        foreach ($files as $file) {
            $res = $this->deleteFile($file['id']);
            if (!$res) $final = false;
        }
        
        return $final;
    }
    
    public function deleteAll($parent, $reference){
        $search = array ('parent_id =' => $parent, 'reference =' => $reference);
        $res = db_q::delete(self::$fileTable)->filterArray($search)->exec();
        return $res;
    }
    
    /**
     * method for adding pre content when files is used as a sub module. 
     * e.g. in content or blog. 
     * @param type $options
     * @return string   content to be displayed
     */
    public static function subModulePreContent ($options){
        $rows = self::getAllFilesInfo($options);
        return self::displayFiles($rows, $options);
    }
    
     /**
     * method for adding pre content when files is used as a sub module. 
     * e.g. in content or blog. 
     * @param type $options
     * @return string   content to be displayed
     */
    public static function subModuleAdminOption ($options){
        $url = moduleloader::buildReferenceURL('/files/add', $options);
        $add_str= lang::translate('Edit files');
        $str = html::createLink($url, $add_str);
        return $str;
    }
    
    /**
     * get admin options as ary ('text', 'url', 'link') when operating as a sub module
     * @param array $options
     * @return array $ary  
     */
    public static function subModuleAdminOptionAry ($options){
        $ary = array ();
        $url = moduleloader::buildReferenceURL('/files/add', $options);
        $text = lang::translate('Edit files');
        $ary['link'] = html::createLink($url, $text);
        $ary['url'] = $url;
        $ary['text'] = $text;
        return $ary;
    }
    
    
    /**
     * generates a file link from a row, title, and link options
     * @param array $val
     * @param string $title
     * @param array $options
     * @return string $link
     */
    public static function getFileLink($val, $title, $options = array ()) {
        return html::createLink(
                       "/files/download/$val[id]/$val[title]", 
                       $title, 
                       $options
                   );
    }
    
    /**
     * method for displaying all files. 
     * @param array $rows
     * @param array $options
     * @return string 
     */
    public static function displayFiles($rows, $options){
        $str = '';
       
        foreach ($rows as $val){
            $title = lang::translate('Download');
            $title.= MENU_SUB_SEPARATOR_SEC;
            $title.= $val['title'];
            
            $link_options = array ('title' => $val['abstract']); 
            $str.= self::getFileLink(
                       $val, 
                       $title, 
                       $link_options
                   );
            
            // as a sub module the sub module can not know anything about the
            // id of individual files. That's why we will add id. 
            //print_r($options);
            $options['id'] = $val['id'];
            if (isset($options['admin'])){
                $str.= MENU_SUB_SEPARATOR_SEC;
                $url = moduleloader::buildReferenceURL('/files/edit', $options);
                $str.= html::createLink($url, lang::translate('Edit'));
                $str.= MENU_SUB_SEPARATOR;
                $url = moduleloader::buildReferenceURL('/files/delete', $options);
                $str.= html::createLink($url, lang::translate('Delete'));
            }
            $str.= "<br />\n";
        }
        return $str;
    }

    /**
     * method for getting all info connected to modules.
     * @param array $options array ('parent_id', 'reference');
     * @return array $rows assoc array with all file info
     */
    public static function getAllFilesInfo($options){
        $db = new db();
        $search = array (  
            'reference' => $options['reference']
        );
        if (isset($options['parent_id'])) {
            $search['parent_id'] = $options['parent_id']; 
        }

        $fields = array ('id', 'parent_id', 'title', 'abstract', 'published', 'created');
        $rows = $db->selectAll(self::$fileTable, $fields, $search, null, null, 'created', false);
        return $rows;
    }
    
    /**
     * method for getting a single files info. 
     * @param int $id
     * @return array $row with info 
     */
    public static function getSingleFileInfo($id = null){
        if (!$id) $id = self::$fileId;
        $db = new db();
        $search = array (
            'id' => $id
        );

        $fields = array ('id', 'parent_id', 'title', 'abstract', 'published', 'created', 'reference');
        $row = $db->selectOne(self::$fileTable, null, $search, $fields, null, 'created', false);
        return $row;
    }


    /**
     * method for fetching one file
     *
     * @return array row with selected files info
     */
    public static function getFile(){
        $db = new db();
        $row = $db->selectOne(self::$fileTable, 'id', self::$fileId);
        return $row;
    }
    
        /**
     * method for fetching one file
     *
     * @return array row with selected files info
     */
    public static function getFileFromTitle($title){
        $db = new db();
        $row = $db->selectOne(self::$fileTable, 'title', $title);
        return $row;
    }

    /**
     * method for updating a module in database
     * (access control is cheched in controller file)
     *
     * @return boolean  true on success or false on failure
     */
    public function updateFile () {
        
        $bind = array();
        $values['abstract'] = html::specialDecode($_POST['abstract']);

        //print_r($_FILES); die;
        if (!empty($_FILES['file']['name']) ){
            
            $options = array ();
            $options['filename'] = 'file';
            $options['maxsize'] = self::$options['maxsize'];
            
            $fp = upload_blob::getFP('file', $options);
            if (!$fp) {
                self::$errors = upload_blob::$errors;
                return false;
            }
            $values['file'] = $fp;
            $values['title'] = $_FILES['file']['name'];
            $values['mimetype'] = $_FILES['file']['type'];
            $values['user_id'] = session::getUserId();

            $bind = array('file' => PDO::PARAM_LOB);
        }
        $db = new db();
        $res = $db->update(self::$fileTable, $values, self::$fileId, $bind);
        return $res;
    }
    
    /**
     * method to be used in a insert controller
     */
    public function viewFileFormInsert(){
        
        //print_r(self::$options);
        //echo 
        if (conf::getModuleIni('files_redirect_parent')) {
             $redirect = moduleloader_reference::getParentEditUrlFromOptions(self::$options);
        } else {
            $redirect = moduleloader::buildReferenceURL('/files/add', self::$options);
        }
        
        if (isset($_POST['submit'])){
            $this->validateInsert();
            if (!isset(self::$errors)){
                $res = $this->insertFile();
                if ($res){
                    session::setActionMessage(lang::translate('File was added'));
                    http::locationHeader($redirect);
                } else {
                    html::errors(self::$errors);
                }
            } else {
                html::errors(self::$errors);
            }
        }
        $this->viewFileForm('insert');
    }

    /**
     * method to be used in a delete controller
     */
    public function viewFileFormDelete(){
        $redirect = moduleloader::buildReferenceURL('/files/add', self::$options);
        if (isset($_POST['submit'])){
            if (!isset(self::$errors)){
                $res = $this->deleteFile(self::$fileId);
                if ($res){
                    session::setActionMessage(
                            lang::translate('File was deleted'));
                    http::locationHeader($redirect);
                }
            } else {
                html::errors(self::$errors);
            }
        }
        $this->viewFileForm('delete', self::$fileId);
    }

    /**
     * merhod to be used in an update controller 
     */
    public function viewFileFormUpdate(){
        $redirect = moduleloader::buildReferenceURL('/files/add', self::$options);
        if (isset($_POST['submit'])){
            if (!isset(self::$errors)){
                $res = $this->updateFile();
                if ($res){
                    session::setActionMessage(
                            lang::translate('File was edited'));
                    http::locationHeader($redirect);
                } else {
                    html::errors(self::$errors);
                }
            } else {
                html::errors(self::$errors);
            }
        }
        $this->viewFileForm('update', self::$fileId);
    }
}

class files_module extends files {}
