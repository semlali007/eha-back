<?php

namespace App\Repository;

use App\Models\Provider;
use App\Models\SheetProvider;
use App\Services\HttpClientService;
use Exception;

class ProviderRepository extends AbstractRepository {

    /**
     * 
     * @param string $word
     * @return mixed
     */
    public function autocomplete(?string $word):mixed {
        $provider = new Provider();
        return $provider->getList(['name' => $word]);
    }

    public function search(?array $options):mixed {
        $provider = new Provider();

        return
                $provider->getPaginatedList($options);
    }

    public function get(mixed $id):mixed {
        $sheetProvider = new SheetProvider();
        return $sheetProvider->getBySheet($id);
    }

    /**
     * 
     * @param array $options
     * @return type
     */
    public function searchFromApi(?array $options):mixed {
        
        $actionCode = HLK["actions"]["providers"]["search"];
        
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
    public function getProvider(?string $word):mixed {
        $actionCode = HLK["actions"]["providers"]["get"];

        if (\Core\Util\DotEnv::isDev()) {
            return \App\Services\MockService::createStatic($actionCode);
        }
        $queryData = [
            'password' => HLK['pwd'],
            'action_code' => $actionCode,
            'action_info' => $word
        ];

        $response =  HttpClientService::exec_post(HLK['url'], $queryData); 
            if(is_null($response)|| empty($response)){
                return [] ;
            }
        return $response ;
    }

    /**
     * 
     * @param array|null $options
     * @return mixed
     */
    public function searchAnchors(?array $options):mixed {
        $actionCode = HLK["actions"]["anchors"]["search"] ;
        
        if(\Core\Util\DotEnv::isDev()){
            return \App\Services\MockService::createStatic($actionCode) ;
        }
        
        $queryData = [
            'password' => HLK['pwd'],
            'action_code' => $actionCode,
            'action_info' => $options['zipcode']
        ];

        return HttpClientService::exec_post(HLK['url'], $queryData);
    }


    public function create($options)
    {
        try {
            if (!isset($options['provider_label']) || empty($options['provider_label'])) {
                throw new Exception('Le nom est obligatoire');
            }
            $provider = new Provider();
            $provider->add($options);
            return [
                'status' => true,
                'element' => $provider->get(['provider_label'=>$options['provider_label']])[0]
            ];
        } catch (Exception $ex) {
            http_response_code(\Core\Http\Response::HTTP_INTERNAL_SERVER_ERROR);
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
