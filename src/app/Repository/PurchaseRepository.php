<?php

namespace App\Repository;

use App\Services\HttpClientService;

class PurchaseRepository extends AbstractRepository {
    /**
     * 
     * @param string $options
     * @return type
     */
    public function searchFromApi($options) {
        $actionCode = HLK["actions"]["purchases"]["search"];
        if (\Core\Util\DotEnv::isDev()) {
            return \App\Services\MockService::createStatic($actionCode);
        }
        
        $queryData = [
            'password' => HLK['pwd'],
            'action_code' => $actionCode,
            'action_info' => $options['words']
        ];
        
        return HttpClientService::exec_post(HLK['url'], $queryData);
         
    }

    /**
     * 
     * @param string $word
     * @return type
     */
    public function getAll(string $word) {
       
        $actionCode = HLK["actions"]["purchases"]["list"];

        
        $queryData = [
            'password' => HLK['pwd'],
            'action_code' => $actionCode,
            'action_info' => $word
        ];

        return HttpClientService::exec_post(HLK['url'], $queryData);
        
        
    }

}
