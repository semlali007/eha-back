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

    public function remove() {
        $options = json_decode(file_get_contents("php://input"), true) ?? [];
        return $this->response($this->providerRepository->delete($options['code']));
    }

    /**
     * POST /providers/update
     * Body : { code: int (requis), provider_label?: string,
     *          provider_siren?: string, provider_numero_tva?: string,
     *          provider_code_naf?: string }
     */
    public function update()
    {
        $options = json_decode(file_get_contents("php://input"), true) ?? [];
        return $this->response($this->providerRepository->update($options));
    }

}
