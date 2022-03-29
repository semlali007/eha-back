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
        $trans = $GLOBALS['configuration']['hlk']['config'];
        $options = Request::input();
        
        if (!array_key_exists("words", $options)):
            $options['words'] = ""; // Afficher toute la listes des DA
        endif;
        
        $resultat = $this->purchaseRepository->getAll($options['words']);
        $firstElements = array_slice($resultat, $options['offset'] * 10, 10);
        $filtered = array_map(function ($valeur)use ($trans) {
            $valeur["last_status_event"] = $trans[$valeur["last_status_event"]];
            return $valeur;
        }, $firstElements);

        return $this->response(['purchases' => $filtered]);
    }

}
