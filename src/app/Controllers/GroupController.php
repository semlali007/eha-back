<?php

namespace App\Controllers;
use App\Manager\GroupManager;
use Database\DBConnection;
use App\Repository\GroupRepository;

class GroupController extends Controller
{
    private  $groupRepository;
    private  $groupManager;
    public function __construct(DBConnection $db, $roles = [])
    {
        parent::__construct($db, $roles);
        $this->groupRepository = new GroupRepository($db);
        $this->groupManager = new GroupManager($db);
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
