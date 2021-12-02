<?php

namespace App\Controllers;

use App\Services\HttpClientService as httpClient ;

class DauphinController extends Controller
{

     /**
     * Exemple appel: http://localhost:8000?url=/items/search/open-source
     * @param string $code
     * @return type
     */
    public function search(string $code) 
    {   if (!\Core\Util\HealthCheck::isActiveHlk()) :
            http_response_code(Response::HTTP_SERVICE_UNAVAILABLE);
            return $this->response(["error" => "HLK Non disponible"]);
        endif;
        $hlk = $GLOBALS['configuration']['hlk'];
        $queryData = [
            'password' => $hlk['pwd'],
            'action_code' => $hlk["actions"]["dauphins"]["search"],
            'action_info' => $code
        ];
        // recupérer la réponse
        $response = httpClient::exec_post($hlk['url'], $queryData);
        return $this->response($response);
    }

    /**
     * Exemple appel: http://localhost:8000?url=/items/show/1060000020
     * @param string $code
     * @return type
     */
    public function show(string $code) {
        if (!\Core\Util\HealthCheck::isActiveHlk()) :
            http_response_code(Response::HTTP_SERVICE_UNAVAILABLE);
            return $this->response(["error" => "HLK Non disponible"]);
        endif;
        $hlk = $GLOBALS['configuration']['hlk'];
        $queryData = [
            'password' => $hlk['pwd'],
            'action_code' => $hlk["actions"]["products"]["get"],
            'action_info' => $code
        ];
        // recupérer la réponse
        $response = httpClient::exec_post($hlk['url'], $queryData);
        return $this->response($response); 
    }
}
