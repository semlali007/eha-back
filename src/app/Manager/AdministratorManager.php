<?php

namespace App\Manager;

use App\Models\Administrator;
use Exception;

class AdministratorManager extends AbstractManager
{
    public function create($options)
    {
        try {
            if (!isset($options['email']) || empty($options['email'])) {
                throw new Exception('Email est obligatoire');
            }
            $admin = new Administrator();
            $admin->add($options['email']);
            return [
                'status' => true,
                'message' => 'email ajouté'
            ];
        } catch (Exception $ex) {
            return [
                'error' => $ex->getCode(),
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
            $admin = new Administrator();
            $admin->delete($email);
            return [
                'status' => true,
                'message' => 'administrateur supprimé'
            ];
        } catch (Exception $ex) {
            return [
                'error' => $ex->getCode(),
                'message' => $ex->getMessage(),
            ];
        }
    }
}
