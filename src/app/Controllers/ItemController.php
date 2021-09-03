<?php

namespace App\Controllers;

use Database\DBConnection;
use App\Services\HttpClientService as httpClient;

class ItemController extends Controller {

    public function __construct(DBConnection $db, $roles = []) {
        parent::__construct($db, $roles);
    }

    /**
     * Exemple appel: http://localhost:8000?url=/items/search/open-source
     * @param string $code
     * @return type
     */
    public function search(string $code) {
        $hlk = $GLOBALS['configuration']['hlk'];
        $queryData = [
            'password' => $hlk['pwd'],
            'action_code' => $hlk["actions"]["items"]["search"],
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
        $hlk = $GLOBALS['configuration']['hlk'];
        $queryData = [
            'password' => $hlk['pwd'],
            'action_code' => $hlk["actions"]["items"]["get"],
            'action_info' => $code
        ];
        // recupérer la réponse
        $response = httpClient::exec_post($hlk['url'], $queryData);
        return $this->response($response);
    }

}
