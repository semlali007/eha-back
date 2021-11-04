<?php

namespace App\Manager;

use App\Models\Group;
use Exception;

class GroupManager extends AbstractManager
{
    public function create($options)
    {
        try {
            if (!isset($options['name']) || empty($options['name'])) {
                throw new Exception('Le nom est obligatoire');
            }
            
            $group = new Group();
            $id =  $group->add($options);
            return [
                'status' => true,
                'id' => $id
            ];
        } catch (Exception $ex) {
            return [
                'error' => $ex->getCode(),
                'message' => $ex->getMessage(),
            ];
        }
    }
}
