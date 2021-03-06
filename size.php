<?php

namespace modules\files;

use diversen\db\q;

/**
 * Class that gets image blobs total size
 */
class size {
   
    /**
     * Get total blobs total size from a parent_id,
     * 
     * @param type $parent
     * @return type
     */
    public function getBlobsSizeFromParentId ($parent) {
        //$parent = q::quote($parent);
        $q = <<<EOF
SELECT sum(length(file)) as total_size from files where parent_id = $parent
EOF;
        $row = q::query($q)->fetchSingle();
        if(empty(trim($row['total_size']))) {
            return 0;
        }
        return  (int)$row['total_size'];
        
    }
}