<?php

namespace App\Repository;

use App\Models\Provider;
use App\Models\SheetProvider;
use App\Services\HttpClientService;
use Exception;

class ProviderRepository extends AbstractRepository {

    public function autocomplete($word) {
        $provider = new Provider($this->db);
        return $provider->getList(['name' => $word]);
    }

    public function search($options) {
        $provider = new Provider($this->db);

        return
                $provider->getPaginatedList($options);
    }

    public function get($id) {
        $sheetProvider = new SheetProvider($this->db);
        return $sheetProvider->getBySheet($id);
    }

    /**
     * 
     * @param string $word
     * @return type
     */
    public function searchFromApi($options) {
            $hlk = $GLOBALS['configuration']['hlk'];
            $queryData = [
                'password' => $hlk['pwd'],
                'action_code' => $hlk["actions"]["providers"]["search"],
                'action_info' => $options['words']
            ];
            return HttpClientService::exec_post($hlk['url'], $queryData); 
    }

    /**
     * 
     * @param string $word
     * @return type
     */
    public function getProvider(string $word) {
      
            $hlk = $GLOBALS['configuration']['hlk'];
            $queryData = [
                'password' => $hlk['pwd'],
                'action_code' => $hlk["actions"]["providers"]["get"],
                'action_info' => $word 
            ];
             
            $response =  HttpClientService::exec_post($hlk['url'], $queryData); 
            if(is_null($response)|| empty($response)){
                return [] ;
            }
            return $response ;
    }

    public function searchAnchors($options) {

        $configExterneApi = CONFIG_EXTERNAL_API;
        $hlkConfig = $configExterneApi['hlk'];
        $apiConfigAnchors = $hlkConfig['anchors']['list'];

        if (strpos($apiConfigAnchors['url'], '.json') !== false) {
            return json_decode(file_get_contents($apiConfigAnchors['url']), true);
        }

        $headers = [
            'Content-Type: application/json',
            'Accept-Charset: utf-8'
        ];

        $queryData = [
            'password' => $hlkConfig['password'],
            'username' => $hlkConfig['username'],
            'limit' => $hlkConfig['limit'],
            'search_text' => $options['words'],
            'line' => $options['line'] ?? ''
        ];

        HttpClientService::prepare($apiConfigAnchors['url'], json_encode($queryData), $headers);
        HttpClientService::exec_post();
        $providers = HttpClientService::get_response_assoc();

        if (!is_array($providers) || count($providers) === 0) {
            throw new Exception('erreur de recupération des données.');
        }

        return $providers;
    }

}
