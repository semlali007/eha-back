<?php

namespace App\Controllers;

use Database\DBConnection;

class DocumentationController extends Controller
{
    public function __construct(DBConnection $db, $roles = [])
    {
        parent::__construct($db, $roles);
    }

    public function index()
    {
        $filename =  getenv('CONFIG_DOCUMENTATION_FILE');
        return $this->response([
            'status' => true,
            'data' => file_get_contents($filename)
        ]);
    }
}
