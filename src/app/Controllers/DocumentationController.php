<?php

namespace App\Controllers;

use App\Repository\DocumentationRepository;
use Core\Http\Request;

class DocumentationController extends Controller {

    private $documentationRepository;

    /**
     * 
     * @param type $roles
     */
    public function __construct($roles = []) {
        parent::__construct($roles);
        $this->documentationRepository = new DocumentationRepository();
    }

    /**
     * Liste des elements dans le menu 
     * @return Array
     */
    public function index() {
        return $this->response($this->documentationRepository->getList());
    }

}
