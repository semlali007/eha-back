<?php

namespace App\Controllers;

use App\Repository\SatisfactionRepository;
use Exception;

class SatisfactionController extends Controller
{
    private  $satisfactionRepository;


    public function __construct($roles = [])
    {
        parent::__construct($roles);
        $this->satisfactionRepository = new SatisfactionRepository();
    }


    public function create()
    {
        try {
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
            $options = json_decode(file_get_contents("php://input"), true) ?? [];
            return $this->response($this->satisfactionRepository->get($nni, $cockpitId));
        } catch (Exception $ex) {
            return $this->error($ex);
        }
    }

    public function list()
    {
        try {
            return $this->response($this->satisfactionRepository->getList());
        } catch (Exception $ex) {
            return $this->error($ex);
        }
    }
    
}
