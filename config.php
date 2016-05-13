<?php

namespace modules\image;

use diversen\session;

/**
 * Image config class
 */
class config {
    
    /**
     * Check if user is collaborator or owner
     * @param int $id image parent_id
     * @return boolean $res
     */
    public function checkAccess ($id) {
        if ($this->isOwner($id) OR $this->isCollab($id) OR session::isAdmin() OR $this->isPublic($id) ) {
            return true;
        } else {
            return false;
        }  
    }
    
    /**
     * Check access to download
     * @param int $id image ID
     * @return boolean $res
     */
    public function checkAccessDownload ($id) {
        // Get image

        $i = new \modules\image\module();
        $image = $i->getSingleFileInfo($id);
        if (empty($image)) {
            return false;
        }
        
        return $this->checkAccess($image['parent_id']);
    }
    
    /**
     * Check is user is collaborator of a book
     * @param int $id book id
     * @return boolean $res
     */
    public function isCollab ($id) {
        $u = new \modules\content\users\module();
        return $u->isUserCollaboratorOnBook(session::getUserId(), $id);
    }
    
    /**
     * Checks if user owns a book
     * @param int $id book id
     * @return boolean $res
     */
    public function isOwner ($id) {
        $b = new \modules\content\book\module();
        return $b->checkAccessOwnBook($id);
    }
    
    /**
     * Checks if a book is public
     * @param type $id
     * @return type
     */
    public function isPublic ($id) {
        $b = new \modules\content\book\module();
        return $b->isPublic($id);
    }
}
