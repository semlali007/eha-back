<?php

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use Database\DBConnection;

class DocumentationController extends Controller {

    public function __construct(DBConnection $db, $roles = []) {
        parent::__construct($db, $roles);
    }

    public function edit() {
        $filename = getenv('CONFIG_DOCUMENTATION_FILE');
        return $this->response([
                    'status' => true,
                    'data' => file_get_contents($filename)
        ]);
    }

    public function update() {
        $post = json_decode(file_get_contents("php://input"));
        var_dump($post,$_REQUEST) ;die ;
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
        }
        $answer = [
            'status' => false,
            'data' => 'Impossible de lire les données'
        ];


        return $this->response($answer);
    }

}
