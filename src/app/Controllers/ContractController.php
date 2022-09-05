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
     * @return type
     */
    public function show(string $code)
    {   
        $params = explode("-",$code) ;
        $contract_code = (count($params)>0) ? $params[0] : "" ;
        $contract = $this->contractRepository->getContract(['word' => $contract_code]);
        $contract['sheets']= [];
        if(isset($contract) && isset($contract['infos']) && isset($contract['infos']['contract_code'])){
            $contract['sheets'] = $this->contractRepository->getSheets($contract['infos']['contract_code']);
        }
        return $this->response($contract);
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
