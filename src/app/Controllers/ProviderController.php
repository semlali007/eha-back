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
        if (!\Core\Util\HealthCheck::isActiveHlk()) :
            http_response_code(Response::HTTP_SERVICE_UNAVAILABLE);
            return $this->response(["error" => \Core\Util\HealthCheck::HLK_ERROR_MSG]);
        endif;
        $options = $this->decode(file_get_contents("php://input"));
        return  $this->response($this->providerRepository->search($options));
    }

    public function show(string $code)
    {   
        if (!\Core\Util\HealthCheck::isActiveHlk()) :
            http_response_code(Response::HTTP_SERVICE_UNAVAILABLE);
            return $this->response(["error" => \Core\Util\HealthCheck::HLK_ERROR_MSG]);
        endif;
        return $this->response($this->providerRepository->getProvider(['code' => $code]));
    }


}
