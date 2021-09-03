<?php

namespace App\Repository;

use App\Services\HttpClientService;
use Exception;

class DauphinRepository extends AbstractRepository
{

    public function searchFromApi($options)
    {

        $hlk = $GLOBALS['configuration']['hlk'];
        $queryData = [
            'password' => $hlk['pwd'],
            'action_code' => $hlk["actions"]["products"]["search"],
            'action_info' => $options['words']
        ];

        return HttpClientService::exec_post($hlk['url'], $queryData);
    }

    public function getDauphin($options)
    {
        $configExterneApi = CONFIG_EXTERNAL_API;
        $hlkConfig = $configExterneApi['hlk'];
        $apiConfigDauphins = $hlkConfig['dauphins']['get'];
        $code = $options['code'];
        if (strpos($apiConfigDauphins['url'], 'mock') !== false) {
            $code = basename($code);
            return json_decode(file_get_contents($apiConfigDauphins['url'] . '/' . $code . '.json'), true);
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
        HttpClientService::prepare($apiConfigDauphins['url'], json_encode($queryData), $headers);
        HttpClientService::exec_post();
        $dauphin = HttpClientService::get_response_assoc();
        if (!is_array($dauphin) || count($dauphin) === 0) {
            throw new Exception('erreur de recupération des données.');
        }

        return  $dauphin;
    }
}
