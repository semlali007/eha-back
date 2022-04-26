<?php

namespace App\Repository;

use App\Models\Provider;
use App\Models\SheetProvider;
use App\Services\HttpClientService;
use Exception;

class ProviderRepository extends AbstractRepository {

    public function autocomplete($word) {
        $provider = new Provider();
        return $provider->getList(['name' => $word]);
    }

    public function search($options) {
        $provider = new Provider();

        return
                $provider->getPaginatedList($options);
    }

    public function get($id) {
        $sheetProvider = new SheetProvider();
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
        $hlk = $GLOBALS['configuration']['hlk'];
        $apiConfigAnchors = $hlk["actions"]["anchors"]["search"];

        if (strpos($apiConfigAnchors, '.json') !== false) {
            return json_decode(file_get_contents(__DIR__ . "/../../public/" . $apiConfigAnchors), true);
        }

        $queryData = [
            'password' => $hlk['pwd'],
            'action_code' => $hlk["actions"]["anchors"]["search"],
            'action_info' => $options['zipcode']
        ];

        return HttpClientService::exec_post($hlk['url'], $queryData);
    }


    public function create($options)
    {
        try {
            if (!isset($options['provider_label']) || empty($options['provider_label'])) {
                throw new Exception('Le nom est obligatoire');
            }
            $provider = new Provider();
            $options['provider_code'] = uniqid("NA");

            $provider->add($options);
            return [
                'status' => true,
                'element' => $provider->get(['provider_label'=>$options['provider_label']])[0]
            ];
        } catch (Exception $ex) {
            http_response_code(500);
            return [
                'error' => $ex->getCode(),
                'message' => $ex->getMessage(),
            ];
        }
    }

    public function delete($providerId)
    {
        try {
            $this->db->getPDO()->beginTransaction();
            (new SheetProvider())->deleteByProviderId($providerId);
            (new Provider())->delete($providerId);
            $this->db->getPDO()->commit();
        } catch (\Exception $e) {
            if ($this->db->getPDO()->inTransaction()) {
                $this->db->getPDO()->rollback();
            }
        }
    }
}
