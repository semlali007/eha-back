<?php

namespace App\Controllers;

use Database\DBConnection;
use App\Repository\DocumentationRepository;
use Core\Http\Request;

class DocumentationController extends Controller {

    private $documentationRepository;

    /**
     * 
     * @param DBConnection $db
     * @param type $roles
     */
    public function __construct(DBConnection $db, array $roles = []) {

        parent::__construct($db, $roles);
        $this->documentationRepository = new DocumentationRepository($db);
    }

    /**
     * Liste des elements dans le menu 
     * @return Array
     */
    public function index() {

        return $this->response($this->documentationRepository->getList(Request::get('parent', 0)));
    }

    /**
     * Liste des elements dans le menu 
     * @return Array
     */
    public function edit() {
        
        $options = Request::input()  ; 
        return $this->response($this->documentationRepository->edit($options));
    }

}
