<?php

namespace App\Manager;

use App\Models\Area;
use Exception;

class NewsCastManager extends AbstractManager
{
    public function create($options)
    {
        try {
            if (!isset($options['name']) || empty($options['name'])) {
                throw new Exception('Le nom est obligatoire');
            }
            
            $area = new Area();
            $id =  $area->add($options);
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
