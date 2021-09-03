<?php

namespace App\Manager\Hotline;

use App\Manager\AbstractManager;
use App\Models\HotlineGroup;
use Exception;

class HotlineGroupManager extends AbstractManager
{
    public function update($options)
    {
        try {

            if (!isset($options['id']) || empty($options['id'])) {
                throw new Exception('id est obligatoire');
            }

            $group = new HotlineGroup($this->db);
            $group->update($options);
            return [
                'status' => true,
                'message' => 'groupe modifié'
            ];
        } catch (Exception $ex) {
            return [
                'status' => false,
                'message' => $ex->getMessage(),
            ];
        }
    }
}
