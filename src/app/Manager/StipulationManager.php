<?php

namespace App\Manager;

use App\Models\Stipulation;
use App\Models\SheetStipulation;
use Exception;

class StipulationManager extends AbstractManager
{
    public function create($options)
    {
        try {
            if (!isset($options['name']) || empty($options['name'])) {
                throw new Exception('Le nom est obligatoire');
            }
            $stipulation = new Stipulation();
            $id =  $stipulation->add($options);
            return [
                'status' => true,
                'element' => $stipulation->getById($id)
            ];
        } catch (Exception $ex) {
            http_response_code(500);
            return [
                'error' => $ex->getCode(),
                'message' => $ex->getMessage(),
            ];
        }
    }


    public function delete($id)
    {
        try {
            $this->db->getPDO()->beginTransaction();
            (new SheetStipulation())->deleteByStipulationId($id);
            (new Stipulation)->delete($id);
            $this->db->getPDO()->commit();
        } catch (\Exception $e) {
            if ($this->db->getPDO()->inTransaction()) {
                $this->db->getPDO()->rollback();
            }
        }
    }

}