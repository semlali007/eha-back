<?php

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Repository\DocumentationRepository;
use Core\Http\Request ;

class DocumentationController extends Controller {

    private $documentationRepository;

    public function __construct($roles = []) {
        parent::__construct($roles);
        $this->documentationRepository = new DocumentationRepository();
    }

    /**
     * Liste des elements dans le menu 
     * @return Array
     */
    public function update() {

        return $this->response($this->documentationRepository->edit(Request::input()));
    }

    /**
     * Liste des elements dans le menu 
     * @return Array
     */
    public function delete($id) {

        return $this->response($this->documentationRepository->delete($id));
    }
    
     /**
     * ajout des elements dans le menu 
     * @return Array
     */
    public function add() {
        return $this->response($this->documentationRepository->add(Request::input()));
    }

    /**
     * Liste des elements dans le menu 
     * @return Array
     */
    public function show($id) {

        return $this->response($this->documentationRepository->details($id));
    }

    public function sort() {

        return $this->response($this->documentationRepository->sort(Request::input()));
    }


    public function export() {
        $data = $this->documentationRepository->getForExport();
        $header = [
            'id',
            'label',
            'description',
            'shortdescription',
            'breadcrumb',
            'external',
            'link',
            'parent',
            'position',
            'warning',
        ];
       
        return $this->response([
            'header' => $header,
            'data' =>  $data 
        ]);
    }

    public function import(){
        return $this->response($this->documentationRepository->import(Request::input()));
    }

   }
