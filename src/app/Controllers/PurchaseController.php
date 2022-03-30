<?php

namespace App\Controllers;

use App\Repository\PurchaseRepository;
use Core\Http\Request ;
class PurchaseController extends Controller
{
    private  $purchaseRepository;
    private $currentNni   ;
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
    
    /**
     * Lister les DA 
     * @return mixed
     */
    public function list(): mixed {
        $hlk = $GLOBALS['configuration']['hlk'] ;
        $apiConfigPurchses= $hlk["actions"]["purchases"]["list"];
        $trans =$hlk['config'];
        
        if (strpos($apiConfigPurchses, '.json') !== false) {
            $data = json_decode(file_get_contents(__DIR__ . "/../../public/" . $apiConfigPurchses),true) ;
            return $this->response(['purchases' => $this->formaTData($data,$trans)]);
        }
        
        $nni = $this->getCurrentUser()->getNni();
        if(is_null($nni)){
            return $this->response(['purchases' =>[]]) ;
        }
        
        $resultat = $this->purchaseRepository->getAll($nni); 
        return $this->response(['purchases' => $this->formaTData($resultat, $trans)]);
    }

    /**
     * 
     * @param array $data
     * @param array $trans
     * @return array
     */
    public function formaTData($data,$trans){
        
         return array_map(function ($valeur)use ($trans) {
            $valeur["last_status_event_label"] =  $valeur["last_status_event"] ;
            if(array_key_exists($valeur["last_status_event"], $trans)){
                $valeur["last_status_event"] = $trans[$valeur["last_status_event"]];
            }else {
                 $valeur["last_status_event"] = 1;
            }
            return $valeur;
        }, $data);
    }
}
