<?php

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use Database\DBConnection;
use App\Repository\DocumentationRepository;
use Core\Http\Request ;

class DocumentationController extends Controller {

    private $documentationRepository;

    public function __construct(DBConnection $db, $roles = []) {
        parent::__construct($db, $roles);
        $this->documentationRepository = new DocumentationRepository($db);
    }

    /**
     * Liste des elements dans le menu 
     * @return Array
     */
    public function edit() {

        return $this->response($this->documentationRepository->edit(Request::input()));
    }

    /**
     * Liste des elements dans le menu 
     * @return Array
     */
    public function delete() {

        return $this->response($this->documentationRepository->delete(Request::input()));
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
    public function show() {

        return $this->response($this->documentationRepository->details(Request::input()));
    }

    public function update() {
        $post = json_decode(file_get_contents("php://input"));
        if (isset($post->data)) {
            if (file_put_contents(getenv('CONFIG_DOCUMENTATION_FILE'), $post->data)) {
                $answer = [
                    'status' => true,
                    'data' => $post->data
                ];
            } else {
                $answer = [
                    'status' => false,
                    'data' => 'Impossible d\'écrire les données'
                ];
            }
        } else {
            $answer = [
                'status' => false,
                'data' => 'Impossible de lire les données'
            ];
        }


        return $this->response($answer);
    }

   }
