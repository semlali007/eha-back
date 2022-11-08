<?php

namespace App\Controllers;

use App\Repository\ContractRepository;

class ContractController extends Controller
{
    private  $contractRepository;
    
    public function __construct($roles = [])
    {
        parent::__construct($roles);
        $this->contractRepository = new ContractRepository();
    }

    
 /**
     * Appel exemple : http://localhost:8000?url=/contracts/show/C92C4T0270-510
     * @param string $code
     * @return mixed
     */
    public function show(string $code): mixed 
    {
        $contract = null;
        $contract4 = $this->contractRepository
                ->getContract(['word' => $code . "-PGI-4"]);
        if (!is_null($contract4)) {
            $contract = $contract4;
        } else {
            $contract2 = $this->contractRepository
                    ->getContract(['word' => $code . "-PGI-2"]);
            if (!is_null($contract2)) {
                $contract = $contract2;
            } else {
                $contract1 = $this->contractRepository
                        ->getContract(['word' => $code . "-PGI-1"]);
                if (!is_null($contract1)) {
                    $contract = $contract1;
                } else {
                   $contract = $this->contractRepository->getContract(['word' => $code]);
                }
            }
        }
        // Parser le Data 
        if (!is_null($contract)) {
            $contract['sheets'] = [];
            if (isset($contract) && isset($contract['infos']) && isset($contract['infos']['contract_code'])) {
                $contract['sheets'] = $this->contractRepository->getSheets($contract['infos']['contract_code']);
            }
            return $this->response($contract);
        }
        return $this->response([]);
    }

    /**
     * Appel exemple : http://localhost:8000?url=/contracts/search/apen_source
     * @description Rechercher un contrat a partir d'une chaine de caractére
     */
    public function searchFor(string $words = "")
    { 
        return $this->response($this->contractRepository->searchFromApi(["word" => $words]));
    }
}
