<?php

namespace App\Controllers;

use Database\DBConnection;
use App\Repository\ProviderRepository;

class ProviderController extends Controller
{
    private  $providerRepository;
    public function __construct(DBConnection $db, $roles = [])
    {
        parent::__construct($db, $roles);
        $this->providerRepository = new ProviderRepository($db);
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
        return $this->response($this->providerRepository->getProvider(['code' => $code]));
    }


}
