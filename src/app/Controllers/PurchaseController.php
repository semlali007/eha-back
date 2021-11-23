<?php

namespace App\Controllers;

use App\Repository\PurchaseRepository;

class PurchaseController extends Controller
{
    private  $purchaseRepository;
    public function __construct($roles = [])
    {
        parent::__construct($roles);
        $this->purchaseRepository = new PurchaseRepository();
    }

    public function search()
    {
        $options = $this->decode(file_get_contents("php://input"));
        if(!isset($options['words']) && empty($options['words'])){
            return  $this->response(
                [
                    'purchases' => [],
                ]
            );
        }

        $purchases =  $this->purchaseRepository->searchFromApi($options);
        return  $this->response(
            [
                'purchases' => $purchases,
            ]
        );
    }
}
