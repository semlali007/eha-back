<?php

namespace App\Controllers;

use App\Manager\BeneficiaryManager;
use App\Repository\BeneficiaryRepository;

class BeneficiaryController extends Controller
{
    private  $beneficiaryRepository;
    private  $beneficiaryManager;
    public function __construct($roles = [])
    {
        parent::__construct($roles);
        $this->beneficiaryRepository = new BeneficiaryRepository();
        $this->beneficiaryManager = new BeneficiaryManager();
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

    public function remove(int $id) {
        return $this->response($this->beneficiaryManager->delete($id));
    }

}
