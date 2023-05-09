<?php

namespace App\Controllers;

use App\Manager\BeneficiaryManager;
use App\Repository\BeneficiaryRepository;

class BeneficiaryController extends Controller
{
    private  $beneficiaryRepository;
    private  $beneficiaryManager;
    public function __construct($roles = [])
    {
        parent::__construct($roles);
        $this->beneficiaryRepository = new BeneficiaryRepository();
        $this->beneficiaryManager = new BeneficiaryManager();
    }

    /**
     * @api {get} beneficiaries/autocomplete/:word rechercher un beneficiaire par son nom
     * @apiName searchBeneficiary
     * @apiGroup Beneficiary
     * @apiParam {String} word .
     */

    public function autocomplete($word)
    {
        return  $this->response($this->beneficiaryRepository->autocomplete($word));
    }

    /**
     * @api {post} beneficiaries/search récupérer la liste de bénéficiaire
     * @apiName listBeneficiary
     * @apiGroup Beneficiary
     */

    public function search()
    {
        $options = $this->decode(file_get_contents("php://input"));
        return  $this->response($this->beneficiaryRepository->search($options));
    }

    /**
     * @api {post} beneficiaries/add ajouter un bénéficiaire à une fiche marché 
     * @apiName addBeneficiary
     * @apiGroup Beneficiary
     */
    
    public function add()
    {
        $options = json_decode(file_get_contents("php://input"), true) ?? [];
        return  $this->response($this->beneficiaryManager->create($options));
    }

    /**
     * @api {post} beneficiaries/remove/:id supprimer un bénéficiaire d'une fiche marché 
     * @apiName removeBeneficiary
     * @apiGroup Beneficiary
     * @apiParam {Integer} id .
     */

    public function remove(int $id) {
        return $this->response($this->beneficiaryManager->delete($id));
    }

}
