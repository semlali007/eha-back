<?php

namespace App\Controllers;

use App\Manager\BeneficiaryManager;
use Database\DBConnection;
use App\Repository\BeneficiaryRepository;

class BeneficiaryController extends Controller
{
    private  $beneficiaryRepository;
    private  $beneficiaryManager;
    public function __construct(DBConnection $db, $roles = [])
    {
        parent::__construct($db, $roles);
        $this->beneficiaryRepository = new BeneficiaryRepository($db);
        $this->beneficiaryManager = new BeneficiaryManager($db);
    }

    public function autocomplete($word)
    {
        return  $this->response($this->beneficiaryRepository->autocomplete($word));
    }

    public function search()
    {
        $options = $this->decode(file_get_contents("php://input"));
        return  $this->response($this->beneficiaryRepository->search($options));
    }

    public function add()
    {
        $options = json_decode(file_get_contents("php://input"), true) ?? [];
        return  $this->response($this->beneficiaryManager->create($options));
    }
}
