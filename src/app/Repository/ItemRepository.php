<?php

namespace App\Repository;

use App\Services\HttpClientService;
use Exception;

class ItemRepository extends AbstractRepository
{

    public function searchFromApi($options)
    {
            $hlk = $GLOBALS['configuration']['hlk'];
            $queryData = [
                'password' => $hlk['pwd'],
                'action_code' => $hlk["actions"]["items"]["search"],
                'action_info' => $options['words']
            ];
            return HttpClientService::exec_post($hlk['url'], $queryData);
    }

    public function getItem($options)
    {
        $configExterneApi = CONFIG_EXTERNAL_API;
        $hlkConfig = $configExterneApi['hlk'];
        $apiConfigItems = $hlkConfig['items']['get'];
        $code = $options['code'];
        if (strpos($apiConfigItems['url'], 'mock') !== false) {
            $code = basename($code);
            return json_decode(file_get_contents($apiConfigItems['url'] . '/' . $code . '.json'), true);
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
        HttpClientService::prepare($apiConfigItems['url'], json_encode($queryData), $headers);
        HttpClientService::exec_post();
        $item = HttpClientService::get_response_assoc();
        if (!is_array($item) || count($item) === 0) {
            throw new Exception('erreur de recupération des données.');
        }

        return  $item;
    }

}
