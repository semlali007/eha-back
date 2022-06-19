<?php

namespace App\Controllers;

use App\Repository\ContractRepository;
use App\Repository\DauphinRepository;
use App\Repository\ItemRepository;
use App\Repository\ProviderRepository;
use Core\Http\Request ;
use Core\Http\Response ;
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

    static function array_filter_by_value($my_array, $index, $value)
    {
        $new_array = [];
        $len = strlen($value);
        if (is_array($my_array) && count($my_array) > 0) {
            foreach ($my_array as $key => $element) {
                if (substr($element[$index], -$len) === $value || $len == 0) {
                    $new_array[] = $element;
                }
            }
        }
        return $new_array;
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
                                'contract' => self::array_filter_by_value($contracts, 'code', $options['system']),
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
