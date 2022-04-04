<?php

namespace App\Controllers;

use App\Repository\PurchaseRepository;
use Core\Http\Request ;
class PurchaseController extends Controller
{
    private  $purchaseRepository;
    public function __construct($roles = [])
    {
        parent::__construct($roles);
        $this->purchaseRepository = new PurchaseRepository();
    }

    /**
     * Lister les DA 
     * @return mixed
     */
    public function search(): mixed {
        
        $options = Request::input();
        if(!array_key_exists("num_da", $options) || (!isset($options['num_da']) && empty($options['num_da']))){
            return $this->response(['purchases' =>[]]) ;
        }
        
        $resultat = $this->purchaseRepository->searchFromApi($options['num_da']); 
        return $this->response(['purchases' => $resultat]);
    }
    
    /**
     * Lister les DA 
     * @return mixed
     */
    public function list(): mixed {
        $hlk = $GLOBALS['configuration']['hlk'] ;
        $trans = $hlk['config'];
        
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
            }else{
                 $valeur["last_status_event"] = -1; 
            }
            return $valeur;
        }, $data);
    }
}
