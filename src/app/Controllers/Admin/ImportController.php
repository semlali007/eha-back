<?php

namespace App\Controllers\Admin;

use App\Controllers\Controller;

class ImportController extends Controller
{
    public function __construct( $roles = [])
    {
        parent::__construct($roles);
    }

    public function upload()
    {
        return  $this->response([
            'status' => true,
            'color' => 'green',
            'message' => 'Fonctionnalité en attente'
        ]);
    }
}