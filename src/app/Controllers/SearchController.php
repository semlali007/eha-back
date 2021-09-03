<?php

namespace App\Controllers;

use App\Repository\ContractRepository;
use App\Repository\DauphinRepository;
use App\Repository\ItemRepository;
use Database\DBConnection;
use App\Repository\ProviderRepository;

class SearchController extends Controller
{
    private  $providerRepository;
    private  $contractRepository;
    private  $dauphinRepository;
    private  $itemRepository;
    public function __construct(DBConnection $db, $roles = [])
    {
        parent::__construct($db, $roles);
        $this->providerRepository = new ProviderRepository($db);
        $this->contractRepository = new ContractRepository($db);
        $this->dauphinRepository = new DauphinRepository($db);
        $this->itemRepository = new ItemRepository($db);
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
