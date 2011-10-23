<?php

/**
 * model file for doing file uploads
 *
 * @package     content
 */

/**
 * @ignore
 */
include_once "upload.php";

/**
 * class content files is used for keeping track of file changes
 * in db. Uses object fileUpload
 */
class files extends db {

    public static $errors = null;
    public static $status = null;
    public static $parent_id;
    public static $fileId;
    public static $maxsize = 2000000; // 2 mb max size
    public static $options = array();
    public static $fileTable = 'files';

    /**
     * constructor sets init vars
     */
    function __construct($options = null){
        $uri = URI::getInstance();
        self::$options = $options;
    }

    public static function setFileId (){
        self::$fileId = uri::$fragments[2];
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
     
        html::formStart('file_upload_form');
        if ($method == 'delete' && isset($id)) {
            $legend = lang::translate('file_delete_label');
            html::legend($legend);
            html::submit('submit', lang::translate('system_submit_delete'));
            echo html::getStr();
            return;
        }
        
        $legend = '';
        if (isset($id)) {
            $values = self::getSingleFileInfo($id);
            html::init($values, 'submit'); 
            $legend = lang::translate('file_edit_legend');
            $submit = lang::system('system_submit_update');
        } else {
            $legend = lang::translate('file_add_legend');
            $submit = lang::system('system_submit_add');
        }
        
        html::legend($legend);
        html::label('abstract', lang::translate('file_abstract_label'));
        html::textareaSmall('abstract');
        html::label('file', lang::translate('file_file_label'));
        html::file('file');
        html::submit('submit', $submit);
        echo html::getStr();
        return;
    }
    
    /**
     * method for inserting a module into the database
     * (access control is cheched in controller file)
     *
     * @return boolean true on success or false on failure
     */
    public function insertFile () {
        $values = $this->prepareToPost();
        $values['parent_id'] = self::$options['parent_id'];
        $values['reference'] = self::$options['reference'];
        $options['filename'] = 'file';
        $options['maxsize'] = self::$maxsize;
        $values['file'] = uploadBlob::getFP($options);
        $values['title'] = $_FILES['file']['name'];
        $values['mimetype'] = $_FILES['file']['type'];
        $bind = array('file' => PDO::PARAM_LOB);
        $res = $this->insert(self::$fileTable, $values, $bind);
        return $res;
    }

    /**
     * method for validating a post before insert
     */
    public function validateInsert($mode = false){
        if (empty($_FILES['file']['name'])){
            self::$errors[] = lang::translate('files_no_file_specified');
        } else {
            if ($_FILES['file']['error'] != 0) {
                $error_message = file_upload_error_message($_FILES['file']['error']);
                self::$errors[] = $error_message;
            }
            if ($_FILES['file']['size'] > return_bytes(ini_get('post_max_size'))){
                self::$errors[] = lang::translate('files_file_too_big') . ' ' .
                              lang::transate('files_maximum_size_is') . " " . ini_get('post_max_size');
            }
        }
    }

    /**
     * method for validating a post before insert
     */
    public function validateUpdate($mode = false){
        if (!empty($_FILES['file']['name'])){
            if ($_FILES['file']['error'] != 0) {
                $error_message = file_upload_error_message($_FILES['file']['error']);
                self::$errors[] = $error_message;
            }
            if ($_FILES['file']['size'] > return_bytes(ini_get('post_max_size'))){
                self::$errors[] = lang::translate('files_file_too_big')  . ' ' .
                              lang::transate('files_maximum_size_is') .  " " . ini_get('post_max_size');
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
        $res = $this->delete(self::$fileTable, 'id', $id);
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
     * method for displaying all files. 
     * @param array $rows
     * @param array $options
     * @return string 
     */
    public static function displayFiles($rows, $options){
        $str = "";
        

        $get = http_build_query($options);
        if (session::isAdmin()){
            $str.= lang::translate('files_attached') . MENU_SUB_SEPARATOR_SEC;
            $add_str= lang::translate('files_add');
            $str.= html::createLink("/files/add?$get", $add_str);
            $str.= "<br />";
        }

        foreach ($rows as $key => $val){
            $title = lang::translate('files_download');
            $title.= MENU_SUB_SEPARATOR_SEC;
            $title.= $val['title'];
            
            $options = array ('title' => $val['abstract']); 
            $str.= html::createLink("/files/download/$val[id]/$val[title]", $title, $options);

            if (session::isAdmin()){
                $str.= MENU_SUB_SEPARATOR_SEC;
                $str.= html::createLink("/files/edit/$val[id]?$get", lang::translate('files_edit'));
                $str.= MENU_SUB_SEPARATOR;
                $str.= html::createLink("/files/delete/$val[id]?$get", lang::translate('files_delete'));
            }
            $str.= "<br />\n";
        }
        return $str;
    }

    /**
     * method for getting all info connected to modules.
     *
     * @return array assoc rows of modules belonging to user
     */
    public static function getAllFilesInfo($options){
        $db = new db();
        $search = array (
            'parent_id' => $options['parent_id'],
            'reference' => $options['reference']
        );

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

    public function displayAllFiles(){
        
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
     * method for updating a module in database
     * (access control is cheched in controller file)
     *
     * @return boolean  true on success or false on failure
     */
    public function updateFile () {
        $bind = array();
        $values = $this->prepareToPost();
        if (!is_uploaded_file($_FILES['file']['tmp_name']) ){
            
        } else {
            // get a file pointer
            $options['filename'] = 'file';
            $options['maxsize'] = self::$maxsize;
            $values['file'] = uploadBlob::getFP($options);
            $values['title'] = $_FILES['file']['name'];

            $values['mimetype'] = $_FILES['file']['type'];

            $bind = array('file' => PDO::PARAM_LOB);
        }
        $res = $this->update(self::$fileTable, $values, self::$fileId, $bind);
        return $res;
    }
    
    /**
     * method to be used in a insert controller
     */
    public function viewFileFormInsert(){
        if (isset($_POST['submit'])){
            $this->validateInsert();
            if (!isset(self::$errors)){
                $res = $this->insertFile();
                if ($res){
                    session::setActionMessage(lang::translate('files_file_added'));
                    $redirect = "Location: " . self::$options['redirect'];
                    header($redirect);
                }
            } else {
                view_form_errors(self::$errors);
            }
        }
        $this->viewFileForm('insert');
    }

    /**
     * method to be used in a delete controller
     */
    public function viewFileFormDelete(){
        if (isset($_POST['submit'])){
            if (!isset(self::$errors)){
                $res = $this->deleteFile(self::$fileId);
                if ($res){
                    session::setActionMessage(lang::translate('files_file_deleted'));
                    $header = "Location: " . self::$options['redirect'];
                    header($header);
                }
            } else {
                view_form_errors(self::$errors);
            }
        }
        $this->viewFileForm('delete', self::$fileId);
    }

    /**
     * merhod to be used in an update controller 
     */
    public function viewFileFormUpdate(){
        if (isset($_POST['submit'])){
            $this->validateUpdate();
            if (!isset(self::$errors)){
                $res = $this->updateFile();
                if ($res){
                    session::setActionMessage(lang::translate('files_file_edited'));
                    $header = "Location: " . self::$options['redirect'];
                    header($header);
                }
            } else {
                view_form_errors(self::$errors);
            }
        }
        $this->viewFileForm('update', self::$fileId);
    }
}
