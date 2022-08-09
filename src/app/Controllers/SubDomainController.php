<?php

namespace App\Controllers;
use App\Manager\SubDomainManager;
use App\Repository\SubDomainRepository;

class SubDomainController extends Controller
{
    private  $repository;
    private  $manager;
    
    public function __construct()
    {
        $this->repository = new SubDomainRepository();
        $this->manager = new SubDomainManager();
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
