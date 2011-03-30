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
include_once "mySqlForm.php";

/**
 * class content files is used for keeping track of file changes
 * in db. Uses object fileUpload
 *
 * @package content
 */
class files extends db {


    public $errors = null;
    public $status = null;
    public $parent_id;
    public $fileId;
    public $maxsize = 2000000; // 2 mb max size
    public $options = array();
    public $fileTable = 'files';

    // {{{ function __construct()
    /**
     *
     * constructor sets init vars
     */
    function __construct($options = null){
        $uri = URI::getInstance();
        //$this->parent_id = $options['parent_id'];
        //$this->fileId = $options['id'];
        if (isset($options)){
            $this->options = $options;
        }
    }
    // }}}
    // used when invoking module as submodule.
    public static function subModuleAdminOptionLink ($options){
        $get = http_build_query($options);
        $url = '/files/add?' . $get;
        $title = 'Add File';
        return html::createLink($url, $title);
    }


    // {{{ viewFilesForm($method, $id = null, $values = array(), $caption = null)
   /**
    * method for creating a form for insert, update and deleting entries
    * in module_system module
    *
    *
    * @param string    method (update, delete or insert)
    * @param int       id (if delete or update)
    */
    public function viewFileForm($method, $id = null, $values = array(), $caption = null){
        $fields_captions =
            array(
                'abstract' => lang::translate('File Abstract'),
                'file' => lang::translate('Upload File'),

        );
        $fields = array('id', 'abstract', 'file');
        $tf = new mySqlForm($this->fileTable, $fields, $values);
        $tf->setLabels($fields_captions);

        if (isset($id)){
            if ($method == 'delete'){
                if (empty($caption)) $caption = lang::translate('Delete File');
            } else {
                //$fields = array('id', 'abstract', 'file');
                $tf = new mySqlForm($this->fileTable, $fields, $values);
                $tf->setLabels($fields_captions);
                if (empty($caption)) $caption = lang::translate('Edit File');
            }
            $tf->setMethod($method, $id);

        } else {
            $tf->setMethod($method);
            if (empty($caption)) $caption = lang::translate('Upload File');
        }
        $tf->createForm('', 'post',  'content_file', '', 'multipart/form-data',  $caption);
    }

    // {{{ insertModuleRelease()
    /**
     * method for inserting a module into the database
     * (access control is cheched in controller file)
     *
     * @return boolean true on success or false on failure
     */
    public function insertFile () {
        $values = $this->prepareToPost();
        $values['parent_id'] = $this->options['parent_id'];
        $values['reference'] = $this->options['reference'];

        $options['filename'] = 'file';
        $options['maxsize'] = $this->maxsize;
        //$options['allow_mime'] = array("application/x-gzip");

        $values['file'] = uploadBlob::getFP($options);
        $values['title'] = $_FILES['file']['name'];
        $values['mimetype'] = $_FILES['file']['type'];
        //application/x-gzip
        //$bind = array( => PDO::PARAM_LOB);
        $bind = array('file' => PDO::PARAM_LOB);
        $res = $this->insert($this->fileTable, $values, $bind);
        return $res;
    }
    // }}}
    // {{{ validateInsert()
    /**
     * method for validating a post before insert
     */
    public function validateInsert($mode = false){
        if (empty($_FILES['file']['name'])){
            $this->errors[] = lang::translate('No file specified');
        } else {
            if ($_FILES['file']['error'] != 0) {
                $error_message = file_upload_error_message($_FILES['file']['error']);
                $this->errors[] = $error_message;
            }
            if ($_FILES['file']['size'] > return_bytes(ini_get('post_max_size'))){
                $this->errors[] = lang::translate('File specified is to big') . ' ' .
                              lang::transate('Maximum size is: ') . ini_get('post_max_size');
            }
        }
    }
    // }}}
    // {{{ validateInsert()
    /**
     * method for validating a post before insert
     */
    public function validateUpdate($mode = false){
        if (!empty($_FILES['file']['name'])){
            if ($_FILES['file']['error'] != 0) {
                $error_message = file_upload_error_message($_FILES['file']['error']);
                $this->errors[] = $error_message;
            }
            if ($_FILES['file']['size'] > return_bytes(ini_get('post_max_size'))){
                $this->errors[] = lang::translate('File specified is to big') . ' ' .
                              lang::transate('Maximum size is: ') . ini_get('post_max_size');
            }
        }
    }
    // }}}
    // {{{ function deleteFile($id) $id = fileId
    /**
     * method for delting a file
     *
     * @param   int     id of file
     * @return  boolean true on success and false on failure
     *
     */
    public function deleteFile($id){
        $res = $this->delete($this->fileTable, 'id', $id);
        return $res;
    }
    // }}}
    // {{{ displayUserModuleReleases($id, $rows)
    public function displayFiles($id, $rows){
        $str = "";
        //$download_str = lang::translate('Download File');

        if (!empty($rows)){
            $str.="<p>" . lang::translate('Files') . "</p>\n";
        }
        print_r($_GET);
        foreach ($rows as $key => $val){

            $str.= "<p>";
            $str.= "<a href=\"/files/download/$id/$val[id]/$val[title]\">$val[title]</a>";
            if (session::isAdmin()){
                $str.=" | ";
                $str.="<a href=\"/files/edit/$id/$val[id]\">";
                $str.=lang::translate('Edit') . "</a>\n";
                $str.=" | ";
                $str.="<a href=\"/files/delete/$id/$val[id]\">";
                $str.=lang::translate('Delete');
                $str.="</a>\n";
            }
            $str.="</p>\n";
            $str.="<p>$val[abstract]</p>\n";
        }

        print $str;
    }
    // }}}
    // {{{ getModuleReleases($moduleId = null)
    /**
     * method for fetching modules belonging to a user
     *
     * @return array assoc rows of modules belonging to user
     */
    public function getAllFiles(){
        $search = array ('parent_id' => $this->parent_id);
        // prevent selecting file by specifiyng fields
        $fields = array ('id', 'parent_id', 'title', 'abstract', 'published', 'created');
        $rows = $this->selectAll($this->fileTable, $fields, $search, null, null, 'created', false);
        return $rows;
    }
    // }}}
    // {{{ displayUserModules
    /**
     * method for displaying all modules belonging to a user
     *
     */
    public function displayAllFiles(){
        $rows = $this->getAllFiles();
        $this->displayFiles ($this->parent_id, $rows);
    }
    // }}}
    // {{{ getFile()
    /**
     * method for fetching one file
     *
     * @return array assoc row with selected module
     */
    public function getFile(){
        $row = $this->selectOne($this->fileTable, 'id', $this->fileId);
        return $row;
    }
    // }}}
    // {{{ updateModuleRelease()
    /**
     * method for updating a module in database
     * (access control is cheched in controller file)
     *
     * @return boolean true on success or false on failure
     */
    public function updateFile () {
        $values = $this->prepareToPost();
        if (!is_uploaded_file($_FILES['file']['tmp_name']) ){

        } else {
            // get a file pointer
            $options['filename'] = 'file';
            $options['maxsize'] = $this->maxsize;
            $values['file'] = uploadBlob::getFP($options);
            $values['title'] = $_FILES['file']['name'];
            $bind = array('file' => PDO::PARAM_LOB);
        }
        $res = $this->update($this->fileTable, $values, $this->fileId, $bind);
        return $res;
    }
    // }}}
    public function viewFileFormInsert(){
        if (isset($_POST['submit'])){
            $this->validateInsert();
            if (!isset($this->errors)){
                $res = $this->insertFile();
                if ($res){
                    session::setActionMessage(lang::translate('File added'));
                    $redirect = "Location: " . $this->options['redirect'];
                    header($redirect);
                }
            } else {
                view_form_errors($this->errors);
            }
        }
        $this->viewFileForm('insert');
    }

    public function viewFileFormDelete(){
        if (isset($_POST['submit'])){
            if (!isset($this->errors)){
                $res = $this->deleteFile($this->fileId);
                if ($res){
                    session::setActionMessage(lang::translate('File deleted'));
                    $header = "Location: " . $this->options['redirect'];
                    header($header);
                }
            } else {
                view_form_errors($this->errors);
            }
        }
        $this->viewFileForm('delete', $this->fileId);
    }

    public function viewFileFormUpdate(){
        if (isset($_POST['submit'])){
            $this->validateUpdate();
            if (!isset($this->errors)){
                $res = $this->updateFile();
                if ($res){
                    session::setActionMessage(lang::translate('File Edited'));
                    $header = "Location: " . $this->options['redirect'];
                    header($header);
                }
            } else {
                view_form_errors($this->errors);
            }
        }
        $this->viewFileForm('update', $this->fileId);
    }
}
// }}}