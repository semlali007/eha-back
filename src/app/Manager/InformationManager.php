<?php

namespace App\Manager;

use App\Models\Information;
use Exception;

class InformationManager extends AbstractManager
{
    public function update($id, $data)
    {
        try {
            $info = new Information();
            $id =  $info->update($id, $data);
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

    public function add($type, $data)
    {
        try {
            $info = new Information();
            $id =  $info->add($type, $data);
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
