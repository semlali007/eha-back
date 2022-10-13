<?php

namespace App\Controllers;
use App\Manager\DomainManager;
use App\Repository\DomainRepository;

class DomainController extends Controller
{
    private  $repository;
    private  $manager;
    
    public function __construct()
    {
        $this->repository = new DomainRepository();
        $this->manager = new DomainManager();
    }

    public function autocomplete($word)
    {
        return  $this->response($this->repository->autocomplete($word));
    }

    public function search()
    {
        $options = $this->decode(file_get_contents("php://input"));
        return  $this->response($this->repository->search($options));
    }

    public function add()
    {
        $options = json_decode(file_get_contents("php://input"), true) ?? [];
        return  $this->response($this->manager->create($options));
    }
}
