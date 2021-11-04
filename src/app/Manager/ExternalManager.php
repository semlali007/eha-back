<?php

namespace App\Manager;

use App\Models\External;
use Exception;

class ExternalManager extends AbstractManager
{
    public function create($options)
    {
        try {
            $external = new External();
            $external->add($options);
            return [
                'status' => true,
                'message' => 'email ajouté'
            ];
        } catch (Exception $ex) {
            return [
                'status' => false,
                'message' => $ex->getMessage(),
            ];
        }
    }
    
    public function delete($email)
    {
        try {
            if (!isset($email) || empty($email)) {
                throw new Exception('Email est obligatoire');
            }
            $external = new External();
            $external->delete($email);
            return [
                'status' => true,
                'message' => 'externe supprimé'
            ];
        } catch (Exception $ex) {
            return [
                'status' => false,
                'message' => $ex->getMessage(),
            ];
        }
    }
}
