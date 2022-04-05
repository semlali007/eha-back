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
    public function search(): mixed
    {
        $hlk = $GLOBALS['configuration']['hlk'] ;
        $trans = $hlk['config'];
        $options = Request::input();
        if (!array_key_exists("num_da", $options) || (!isset($options['num_da']) && empty($options['num_da']))) {
            return $this->response(['purchase' => []]);
        }
        $options['words'] = $options['num_da'];
        $statuses = $hlk['status'];

        $resultat = $this->purchaseRepository->searchFromApi($options);
        $resultat = $this->formaTData($resultat, $trans, 'event_label');

        $keys = array_unique(array_column($resultat, 'event_label')); // Les clés
        $purchases = [] ; // initialiser les purchases
        foreach ($keys as $key) { // Parcourir les clés
             $ids = array_keys(array_column($resultat, 'event_label'),$key ); // retourne les ids
             $items = [] ;
             foreach($ids as $id){
                 array_push($items, $resultat[$id]) ;
             }
             array_push($purchases,["statut"=>$key, "items"=>$items]) ;
         }
         
       
        return $this->response(['purchase' =>  $purchases]);
    }
    
    /**
     * Lister les DA 
     * @return mixed
     */
    public function list(): mixed {
        $hlk = $GLOBALS['configuration']['hlk'] ;
        $trans = $hlk['config'];
        
        $nni = $this->getCurrentUser()->getNni();
        $nni= '';
        if(is_null($nni)){
            return $this->response(['purchases' =>[]]) ;
        }
        
        $resultat = $this->purchaseRepository->getAll($nni); 

        return $this->response(['purchases' => $this->formaTData($resultat, $trans, 'last_status_event')]);
    }
    
    /**
     * 
     * @param array $data
     * @param array $trans
     * @return array
     */
    public function formaTData($data,$trans, $key){
        
         return array_map(function ($valeur)use ($trans, $key) {
            $valeur["last_status_event_label"] =  $valeur[$key] ;
            if(array_key_exists($valeur[$key], $trans)){
                $valeur[$key] = $trans[$valeur[$key]];
            }else{
                 $valeur[$key] = -1; 
            }
            return $valeur;
        }, $data);
    }
}
