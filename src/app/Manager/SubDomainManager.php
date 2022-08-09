<?php

namespace App\Manager;

use App\Models\SubDomain;
use Exception;

class SubDomainManager extends AbstractManager
{
    public function create($options)
    {
        try {
            if (!isset($options['name']) || empty($options['name'])) {
                throw new Exception('Le nom est obligatoire');
            }
            
            $group = new SubDomain();
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
