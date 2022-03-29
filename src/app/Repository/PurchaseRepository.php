<?php

namespace App\Repository;

use App\Services\HttpClientService;

class PurchaseRepository extends AbstractRepository {
    /**
     * 
     * @param string $word
     * @return type
     */
    public function searchFromApi($options) {
            $hlk = $GLOBALS['configuration']['hlk'];
            $queryData = [
                'password' => $hlk['pwd'],
                'action_code' => $hlk["actions"]["purchases"]["search"],
                'action_info' => $options['words']
            ];

            return HttpClientService::exec_post($hlk['url'], $queryData); 
    }
    
    /**
     * 
     * @param string $options
     * @return type
     */
    public function getAll(string $word) {
            $hlk = $GLOBALS['configuration']['hlk'];
            $queryData = [
                'password' => $hlk['pwd'],
                'action_code' => $hlk["actions"]["purchases"]["list"],
                'action_info' => $word
            ];

            return HttpClientService::exec_post($hlk['url'], $queryData); 
    }
}
