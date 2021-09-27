<?php

namespace App\Controllers;

use Database\DBConnection;
use App\Repository\DocumentationRepository ;
class DocumentationController extends Controller {

    private $documentationRepository ;
    
    public function __construct(DBConnection $db, $roles = []) {
        
        parent::__construct($db, $roles);
        $this->documentationRepository = new DocumentationRepository($db);
        
    }

    public function index() {
      
        return $this->response($this->documentationRepository->getList());
    }

}
