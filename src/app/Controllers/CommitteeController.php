<?php

namespace App\Controllers;

use Database\DBConnection;
use App\Repository\CommitteeRepository;
use Exception;

class CommitteeController extends Controller
{
    private  $committeeRepository;


    public function __construct()
    {
        $this->committeeRepository = new CommitteeRepository();
    }


    public function index()
    {
        try {
            return $this->response($this->committeeRepository->getList());
        } catch (Exception $ex) {
            return $this->error($ex);
        }
    }
}
