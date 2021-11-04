<?php

namespace App\Controllers;
use App\Manager\GroupManager;
use App\Repository\GroupRepository;

class GroupController extends Controller
{
    private  $groupRepository;
    private  $groupManager;
    
    public function __construct()
    {
        $this->groupRepository = new GroupRepository();
        $this->groupManager = new GroupManager();
    }

    public function autocomplete($word)
    {
        return  $this->response($this->groupRepository->autocomplete($word));
    }

    public function search()
    {
        $options = $this->decode(file_get_contents("php://input"));
        return  $this->response($this->groupRepository->search($options));
    }

    public function add()
    {
        $options = json_decode(file_get_contents("php://input"), true) ?? [];
        return  $this->response($this->groupManager->create($options));
    }
}
