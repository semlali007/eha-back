<?php

namespace App\Controllers;

use App\Repository\ContractRepository;
use App\Repository\DauphinRepository;
use App\Repository\ItemRepository;
use App\Repository\ProviderRepository;
use Core\Http\Request ;
use Core\Util\Arrays ;
use App\Exceptions\HttpException ;
use App\Services\HttpClientService;
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
            $providers = [];
            $data = $this->providerRepository->serachMdmProviders($options);
            foreach($data as $p ){
                $providers[] = [
                    'code' => $p['supplier_business_unit_app_id'],
                    'label' => $p['supplier_business_legal_registration_name'],
                    'other' => 'SIRET: ' . $p['supplier_business_unit_registration_id'] . ', Adresse: ' . $p['supplier_business_unit_address_street']
                     .' '. $p['supplier_business_unit_address_postal_zone'].' '. $p['supplier_business_unit_address_town']
                ];
            }
        
            $contracts = $this->contractRepository->searchFromApi($options);
            $dauphins = $this->dauphinRepository->searchFromApi($options);
            
	if (self::isCodeItem($options['words']) ) {
                $hlk = $GLOBALS['configuration']['hlk'];
                $queryData = [
                    'password' => $hlk['pwd'],
                    'action_code' => $hlk["actions"]["items"]["get"],
                    'action_info' => $options['words']
                ];

                $itemsData = HttpClientService::exec_post($hlk['url'], $queryData);
                $items = [];
                if(is_array($itemsData)){
                    $items[] = [
                        'code' => $itemsData['infos']['item_code'],
                        'label' => $itemsData['infos']['item_label'],
                        'other' => $itemsData['infos']['item_label_long']
                    ];
                }
            } else {
                $items = $this->itemRepository->searchFromApi($options);
            }	
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

   static function isCodeItem(string $searchInput): ?bool {
        if (strlen($searchInput) != 10) {
            return false;
        }

        preg_match_all("/106[0-9]{7}/", $searchInput, $matches, PREG_PATTERN_ORDER);
        foreach (reset($matches) as $code) {
            if (preg_match("#\d#", $code)) {
               return true ;
            }
        }
        
        return false;
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
                if(!in_array($provider['provider_nace'], $options['nace'] ))
                {
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
