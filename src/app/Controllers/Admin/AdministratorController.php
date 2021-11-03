<?php

namespace App\Controllers\Admin;
use App\Controllers\Controller;

use App\Manager\AdministratorManager;
use App\Repository\AdministratorRepository;

class AdministratorController extends Controller
{
    private  $administratorRepository;
    private  $administratorManager;
    public function __construct($roles = [])
    {
        parent::__construct($roles);
        $this->administratorRepository = new AdministratorRepository();
        $this->administratorManager = new AdministratorManager();
    }

    public function index()
    {
        try {
            return  $this->response(
                [
                    'status' => true,
                    'rows' => $this->administratorRepository->getList()
                ]
            );
        } catch (\Throwable $th) {
            return  $this->response(
                [
                    'status' => false,
                ]
            );
        }
    }

    public function create()
    {
        $options = json_decode(file_get_contents("php://input"), true) ?? [];
      
        return  $this->response($this->administratorManager->create($options));
    }

    public function delete($email)
    {
        return  $this->response($this->administratorManager->delete($email));
    }
}
