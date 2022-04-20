<?php

namespace App\Controllers;

use App\Repository\ContractRepository;
use App\Repository\DauphinRepository;
use App\Repository\ItemRepository;
use App\Repository\ProviderRepository;
use Core\Http\Request ;
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
        $options = $this->decode(file_get_contents("php://input"));
        $providers =  $this->providerRepository->searchFromApi($options);
        $contracts =  $this->contractRepository->searchFromApi($options);
        $dauphins =  $this->dauphinRepository->searchFromApi($options);
        $items =  $this->itemRepository->searchFromApi($options);
        return  $this->response(
            [
                'provider' => $providers,
                'contract' => $contracts,
                'dauphin' => $dauphins,
                'item_generic' => $items,
            ]
        );
    }
    
    /**
     * 
     * @return mixed
     */
    public function getAnchors():mixed
    {
        $providers = $this->providerRepository->searchAnchors(Request::input()) ;
        return  $this
                ->response(["statut" => true,"providers" => $providers]);
    }
    
}
