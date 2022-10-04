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
        if (strlen($code) <= 11) {// Sans PGI
            $PGI_CODES = ["PGI-4", "PGI-2", "PGI-1"];
            $i = 0;
            do {
                $contract = $this->contractRepository
                        ->getContract(['word' =>$code . "-" . $PGI_CODES[$i]]);
                $i++;
            } while (($i <= 2) || is_null($contract));
            // Avec PGI
        } else { 
            $contract = $this->contractRepository->getContract(['word' => $code]);
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
