<?php

namespace App\Controllers;

use Database\DBConnection;
use App\Repository\CommitteeRepository;
use Exception;

class CommitteeController extends Controller
{
    private  $committeeRepository;


    public function __construct(DBConnection $db, $roles = [])
    {
        parent::__construct($db, $roles);
        $this->committeeRepository = new CommitteeRepository($db);
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
