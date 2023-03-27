<?php

namespace App\Controllers;

use App\Repository\PurchaseRepository;
use Core\Http\Request ;
use Core\Util\Status ;
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
        try {
            $trans = HLK['status'];
            $options = Request::input();
            if (!array_key_exists("num_da", $options) || (!isset($options['num_da']) && empty($options['num_da']))) {
                return $this->response(['purchase' => []]);
            }
            $options['words'] = $options['num_da'];
            $search = $this->purchaseRepository->searchFromApi($options);
            $resultat = $this->formaTData($search, $trans, 'event_label');
            return $this->response(['items' => $resultat]);
        } catch (\Throwable|\Exception $ex) {
            return $this
                            ->hlkErrorResponse();
        }
    }
    
    /**
     * Lister les DA 
     * @return mixed
     */
    public function list(): mixed {
        try {
            $trans = HLK['status'];
            $nni = $this->getCurrentUser()->getNni();
            $arr = ["HGDDEDBN", "WID03C5N", "ETF93B9N", "G68670"];
            if (in_array($nni, $arr)) {
                $nni = 'E85949';
            }
            if (is_null($nni)) {
                return $this->response(['purchases' => []]);
            }
           
            $resultat = $this->formaTData($this->purchaseRepository->getAll($nni), $trans, 'last_status_event');
            return $this->response(['purchases' => $resultat]);
        } catch (\Throwable|\Exception $ex) {
            return $this->hlkErrorResponse();
        }
    }
    
    /**
     * 
     * @param array $data
     * @param array $trans
     * @return array
     */
    public function formaTData($data,$trans, $key){

        return array_map(function ($valeur) use ($trans, $key) {
            $valeur["last_status_event_label"] =  $valeur[$key];
            if (array_key_exists($key, $valeur) && array_key_exists($valeur[$key], $trans)) {
                    $valeur[$key] = $trans[$valeur[$key]];
                } else {
                    $valeur[$key] = Status::INITIALISEE;
                }
            return $valeur;
        }, $data);
    }

    public function listV2(): mixed {
        try {
            $trans = HLK['status'];
            $nni = $this->getCurrentUser()->getNni();
            $arr = ["HGDDEDBN", "WID03C5N", "ETF93B9N", "G68670"];
            if (in_array($nni, $arr)) {
                $nni = 'E85949';
            }
            if (is_null($nni)) {
                return $this->response(['purchases' => []]);
            }

            return $this->response(['purchases' => $this->purchaseRepository->getAllPurchases($nni)]);
        } catch (\Throwable|\Exception $ex) {
            return $this->hlkErrorResponse();
        }
    }
}
