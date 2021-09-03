<?php

namespace App\Manager;

use App\Models\Beneficiary;
use Exception;

class BeneficiaryManager extends AbstractManager
{
    public function create($options)
    {
        try {
            if (!isset($options['name']) || empty($options['name'])) {
                throw new Exception('Le nom est obligatoire');
            }
            $beneficiary = new Beneficiary($this->db);
            $id =  $beneficiary->add($options);
            return [
                'status' => true,
                'id' => $id
            ];
        } catch (Exception $ex) {
            http_response_code(500);
            return [
                'error' => $ex->getCode(),
                'message' => $ex->getMessage(),
            ];
        }
    }
}