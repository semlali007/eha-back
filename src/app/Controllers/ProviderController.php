<?php

namespace App\Controllers;

use App\Repository\ProviderRepository;

class ProviderController extends Controller
{
    private  $providerRepository;
    
    public function __construct($roles = [])
    {
        parent::__construct($roles);
        $this->providerRepository = new ProviderRepository();
    }

    public function autocomplete($word)
    {
        return  $this->response($this->providerRepository->autocomplete($word));
    }

    public function search()
    {
        $options = $this->decode(file_get_contents("php://input"));
        return  $this->response($this->providerRepository->search($options));
    }

    public function show(string $code)
    {
        return $this->response($this->providerRepository->getProvider($code));
    }
    public function add()
    {
        $options = json_decode(file_get_contents("php://input"), true) ?? [];
        return  $this->response($this->providerRepository->create($options));
    }

}
