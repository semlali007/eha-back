<?php

namespace App\Controllers;

use App\Repository\ContractRepository;
use App\Repository\DauphinRepository;
use App\Repository\ItemRepository;
use App\Repository\ProviderRepository;

class SearchController extends Controller
{
    private  $providerRepository;
    private  $contractRepository;
    private  $dauphinRepository;
    private  $itemRepository;
    public function __construct()
    {
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

    public function getAnchors()
    {
        $options = $this->decode(file_get_contents("php://input"));
        $anchors =  $this->providerRepository->searchAnchors($options);
        return  $this->response($anchors);
    }
    
}
