<?php

namespace App\Controllers;

use App\Repository\ContractRepository;

class ContractController extends Controller
{
    private  $contractRepository;
    
    public function __construct()
    {
        $this->contractRepository = new ContractRepository();
    }

    /**
     * Appel exemple : http://localhost:8000?url=/contracts/show/C92C4T0270-510
     * @param string $code
     * @return type
     */
    public function show(string $code)
    {
        return $this->response($this->contractRepository->getContract(['word' => $code]));
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
