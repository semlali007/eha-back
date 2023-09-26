<?php

namespace App\Controllers;

use App\Repository\SatisfactionRepository;
use Exception;

class SatisfactionController extends Controller
{
    private  $satisfactionRepository;
    private const API_KEY = "T8993KP#9Eo2";


    public function __construct($roles = [])
    {
        parent::__construct($roles);
        $this->satisfactionRepository = new SatisfactionRepository();
    }


    // public function create()
    // {
    //     try {
    //         $options = json_decode(file_get_contents("php://input"), true) ?? [];
    //        $this->response($this->satisfactionRepository->create($options));
    //        return $this->response($this->satisfactionRepository->get($options['nni'], $options['cockpit_id']));
    //     } catch (Exception $ex) {
    //         return $this->error($ex);
    //     }
    // }

    // public function get($nni, $cockpitId)
    // {
    //     try {
    //         $options = json_decode(file_get_contents("php://input"), true) ?? [];
    //         return $this->response($this->satisfactionRepository->get($nni, $cockpitId));
    //     } catch (Exception $ex) {
    //         return $this->error($ex);
    //     }
    // }

    // public function list()
    // {
    //     try {
    //         return $this->response($this->satisfactionRepository->getList());
    //     } catch (Exception $ex) {
    //         return $this->error($ex);
    //     }
    // }
    public function create()
    {
        try {
            $apiKey = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            
            if ($apiKey !== self::API_KEY) {
                return $this->error("Clé authentification invalide");
            }

            $options = json_decode(file_get_contents("php://input"), true) ?? [];
            $this->response($this->satisfactionRepository->create($options));
            return $this->response($this->satisfactionRepository->get($options['nni'], $options['cockpit_id']));
        } catch (Exception $ex) {
            return $this->error($ex);
        }
    }

    public function get($nni, $cockpitId)
    {
        try {
            $apiKey = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            
            if ($apiKey !== self::API_KEY) {
                return $this->error("Clé d'authentification invalide");
            }

            $options = json_decode(file_get_contents("php://input"), true) ?? [];
            return $this->response($this->satisfactionRepository->get($nni, $cockpitId));
        } catch (Exception $ex) {
            return $this->error($ex);
        }
    }

    public function list()
    {
        try {
            $apiKey = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

            // Vérifier si la clé 
            if ($apiKey !== self::API_KEY) {
                return $this->error("Clé d'authentification invalide");
            }

            return $this->response($this->satisfactionRepository->getList());
        } catch (Exception $ex) {
            return $this->error($ex);
        }
    }
}
