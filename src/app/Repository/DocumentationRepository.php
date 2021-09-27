<?php

namespace App\Repository;

use App\Models\Documentation ;
/**
 * Description of DocumentationRepository
 *
 * @author HGDDEDBN
 */
class DocumentationRepository extends AbstractRepository{
 
 

    /**
     * 
     * @return array|null
     */
    public function getList(int $parent = 0):?array
    {
        return (new Documentation($this->db))->getByParentId($parent);
    }
}