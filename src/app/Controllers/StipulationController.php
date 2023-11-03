<?php

namespace App\Controllers;

use App\Manager\StipulationManager;
use App\Repository\StipulationRepository;

class StipulationController extends Controller
{
    private  $stipulationRepository;
    private  $stipulationManager;
    public function __construct($roles = [])
    {
        parent::__construct($roles);
        $this->stipulationRepository = new StipulationRepository();
        $this->stipulationManager = new StipulationManager();
    }

    /**
     * @api {get} stipulationAutrui/autocomplete/:word rechercher un stipulationAutrui par son nom
     * @apiName searchStipulation
     * @apiGroup Stipulation
     * @apiParam {String} word .
     */

    public function autocomplete($word)
    {
        return  $this->response($this->stipulationRepository->autocomplete($word));
    }

    /**
     * @api {post} stipulationAutrui/search récupérer la liste de stipulationAutrui
     * @apiName listStipulation
     * @apiGroup Stipulation
     */

    public function search()
    {
        $options = $this->decode(file_get_contents("php://input"));
        return  $this->response($this->stipulationRepository->search($options));
    }

    /**
     * @api {post} stipulationAutrui/add ajouter un bénéficiaire à une fiche marché 
     * @apiName addStipulation
     * @apiGroup Stipulation
     */
    
    public function add()
    {
        $options = json_decode(file_get_contents("php://input"), true) ?? [];
        return  $this->response($this->stipulationManager->create($options));
    }

    /**
     * @api {post} stipulationAutrui/remove/:id supprimer un stipulationAutrui d'une fiche marché 
     * @apiName removeStipulation
     * @apiGroup Stipulation
     * @apiParam {Integer} id .
     */

    public function remove(int $id) {
        return $this->response($this->stipulationManager->delete($id));
    }

}
