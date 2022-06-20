<?php

namespace App\Controllers;

use App\Repository\ContractRepository;
use App\Repository\DauphinRepository;
use App\Repository\ItemRepository;
use App\Repository\ProviderRepository;
use Core\Http\Request ;
use Core\Util\Arrays ;
use App\Exceptions\HttpException ;
use Core\Util\HealthCheck as health;

use function PHPSTORM_META\type;

class SearchController extends Controller
{
    private  $providerRepository;
    private  $contractRepository;
    private  $dauphinRepository;
    private  $itemRepository;
    public function __construct($roles = [])
    {
        parent::__construct($roles);
        $this->providerRepository = new ProviderRepository();
        $this->contractRepository = new ContractRepository();
        $this->dauphinRepository = new DauphinRepository();
        $this->itemRepository = new ItemRepository();
    }



    public function search()
    {
        $options = Request::input();
        
        try {
            $providers = $this->providerRepository->searchFromApi($options);
            $contracts = $this->contractRepository->searchFromApi($options);
            $dauphins = $this->dauphinRepository->searchFromApi($options);
            $items = $this->itemRepository->searchFromApi($options);
            return $this->response(
                            [
                                'provider' => $providers,
                                'contract' => Arrays::filterByValue($contracts, 'code', $options['system']),
                                'dauphin' => $dauphins,
                                'item_generic' => $items,
                            ]
            );
        } catch (HttpException $ex) {
            return $this
                            ->hlkErrorResponse();
        }
    }
    
    /**
     * 
     * @return mixed
     */
    public function getAnchors():mixed
    {
       
        $options = Request::input();
        try{
        $providers = $this->providerRepository->searchAnchors($options) ;
        $result = [];
      
        // dans le cas ou l'element n'existe pas
        foreach ($providers as $provider) {

            if (isset($options['nace']) && !empty($options['nace'])) {
                if ($provider['provider_nace'] != $options['nace']) {
                    continue;
                }
            }

            if (isset($options['articles']) && !empty($options['articles'])) {
                $articles = explode(',',$options['articles']);
                if (!is_array($articles) || !in_array($provider['provider_code'], $articles)) {
                    continue;
                }
            }

            if(!array_key_exists("activities", $provider)):
                $provider["activities"] = ["TP:1"]; // en dur
            endif ;

            $result[]=$provider;
        }
        return  $this
                ->response(["statut" => true,"providers" => $result]);
        
         } catch (HttpException $ex) {
             return  $this
                ->hlkErrorResponse();
        } 
    }
    
}
