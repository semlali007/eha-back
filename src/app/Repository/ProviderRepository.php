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
            
            $queryData = [
                'password' => HLK['pwd'],
                'action_code' => HLK["actions"]["providers"]["search"],
                'action_info' => $options['words']
            ];
            
            return HttpClientService::exec_post(HLK['url'], $queryData); 
    }

    /**
     * 
     * @param string $word
     * @return type
     */
    public function getProvider(string $word):mixed {
      
            $queryData = [
                'password' => HLK['pwd'],
                'action_code' => HLK["actions"]["providers"]["get"],
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
       
        $queryData = [
            'password' => HLK['pwd'],
            'action_code' => HLK["actions"]["anchors"]["search"],
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
}
