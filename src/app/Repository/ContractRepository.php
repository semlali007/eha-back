<?php

namespace App\Repository;

use App\Models\SheetContract;
use App\Models\Sheet;
use App\Services\HttpClientService;
use Exception;

class ContractRepository extends AbstractRepository {

    /**
     * 
     * @param array $options
     * @return array|null
     */
    public function searchFromApi(array $options): ?array {

        $globalConf = $GLOBALS['configuration'];
        if (isset($globalConf['hlk']) && array_key_exists("hlk", $globalConf)):
            $hlk = $GLOBALS['configuration']['hlk'];
            $queryData = [
                'password' => $hlk['pwd'],
                'action_code' => $hlk["actions"]["contracts"]["search"],
                'action_info' => $options['words'],
                'limit' => $hlk['limit'],
                'line' => $options['line'] ?? ''
            ];
            return HttpClientService::exec_post($hlk['url'], $queryData);
        endif;
        return [];
    }

    /**
     * 
     * @param type $options
     * @return type
     * @throws Exception
     */
    public function getContract($options) {

        $globalConf = $GLOBALS['configuration'];
        if (isset($globalConf['hlk']) && array_key_exists("hlk", $globalConf)):
            $hlk = $GLOBALS['configuration']['hlk'];
            $queryData = [
                'password' => $hlk['pwd'],
                'action_code' => $hlk["actions"]["contracts"]["get"],
                'action_info' => $options['word'],
                'limit' => $hlk['limit'],
                'line' => $options['line'] ?? ''
            ]; 
            return HttpClientService::exec_post($hlk['url'], $queryData);
        endif;
        return [];
    }
    public function getSheets($code){
        $sheet = new Sheet();
        return $sheet->getByContract($code);
    
    }

}
