<?php

namespace App\Controllers;

use Database\DBConnection; 
use App\Services\HttpClientService ;

class ProductController extends Controller {
 
    public function __construct(DBConnection $db, $roles = []) {
        parent::__construct($db, $roles);
       
    }

    /**
     * Exemple appel: http://localhost:8000?url=/products/search/open-source
     * @param string $code
     * @return type
     */
    public function search(string $code) 
    {
        $hlk = $GLOBALS['configuration']['hlk'];
        $queryData = [
            'password' => $hlk['pwd'],
            'action_code' => $hlk["actions"]["products"]["search"],
            'action_info' => $code
        ];
        // recupérer la réponse
        $response = HttpClientService::exec_post($hlk['url'], $queryData);
        return $this->response($response);
    }

    /**
     * Exemple appel: http://localhost:8000?url=/providers/show/753879
     * @param string $code
     * @return type
     */
    public function show(string $code) {
        $hlk = $GLOBALS['configuration']['hlk'];
        $queryData = [
            'password' => $hlk['pwd'],
            'action_code' => $hlk["actions"]["products"]["get"],
            'action_info' => $code
        ];
        // recupérer la réponse
        $response = HttpClientService::exec_post($hlk['url'], $queryData);
        return $this->response($response); 
    }

}
