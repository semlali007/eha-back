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


    public function getAllPurchases(string $nni) {
        $actions = HLK['v2']["actions"];
        $pgiNumberAction = $actions["search_by_nni"]["search_by"];
        $queryData = [
            'search_by' => $pgiNumberAction,
            'value' => $nni
        ];
        return HttpClientService::exec_get(HLK['v2']['url'].'/'.$actions["search_by_nni"]['api'], $queryData, ["API-HLK-Key: " . HLK['v2']['pwd']]);
    }

}
