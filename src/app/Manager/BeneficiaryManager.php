<?php

namespace App\Manager;

use App\Models\Beneficiary;
use App\Models\SheetBeneficiary;
use Exception;

class BeneficiaryManager extends AbstractManager
{
    public function create($options)
    {
        try {
            if (!isset($options['name']) || empty($options['name'])) {
                throw new Exception('Le nom est obligatoire');
            }
            $beneficiary = new Beneficiary();
            $id =  $beneficiary->add($options);
            return [
                'status' => true,
                'element' => $beneficiary->getById($id)
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
            (new SheetBeneficiary())->deleteByBeneficiaryId($id);
            (new Beneficiary)->delete($id);
            $this->db->getPDO()->commit();
        } catch (\Exception $e) {
            if ($this->db->getPDO()->inTransaction()) {
                $this->db->getPDO()->rollback();
            }
        }
    }

}