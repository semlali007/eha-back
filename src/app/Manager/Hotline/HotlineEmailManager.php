<?php

namespace App\Manager\Hotline;

use App\Manager\AbstractManager;
use App\Models\HotlineEmail;
use Exception;

class HotlineEmailManager extends AbstractManager
{
    public function update($options)
    {
        try {
            if (!isset($options['id']) || empty($options['id'])) {
                throw new Exception('id est obligatoire');
            }
            if (!isset($options['id']) || empty($options['id'])) {
                throw new Exception('email est obligatoire');
            }

            $email = new HotlineEmail();
            $email->update($options);
            return [
                'status' => true,
                'message' => 'email modifié'
            ];
        } catch (Exception $ex) {
            return [
                'status' => false,
                'message' => $ex->getMessage(),
            ];
        }
    }

    public function livingUpdate($options)
    {
        try {
            if (!isset($options['id']) || empty($options['id'])) {
                throw new Exception('id est obligatoire');
            }

            $email = new HotlineEmail();
            $email->livingUpdate($options['id']);
            return [
                'status' => true,
                'message' => 'email modifié'
            ];
        } catch (Exception $ex) {
            return [
                'status' => false,
                'message' => $ex->getMessage(),
            ];
        }
    }
}
