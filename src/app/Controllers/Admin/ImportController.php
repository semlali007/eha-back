<?php

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use Database\DBConnection;

class ImportController extends Controller
{
    public function __construct(DBConnection $db, $roles = [])
    {
        parent::__construct($db, $roles);
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