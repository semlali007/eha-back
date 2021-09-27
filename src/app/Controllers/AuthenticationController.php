<?php

namespace App\Controllers;

use App\Models\External;
use App\Models\Administrator;
use App\Services\EntityService;
use Database\DBConnection;
use Core\Http\Request ;

class AuthenticationController extends Controller
{
    public function __construct(DBConnection $db, $roles = [])
    {
        parent::__construct($db, $roles);
    }

    public function login()
    {
        
    }

    public function logout()
    {
        session_start();
        unset($_SESSION['user']);
        return $this->response(
            [
                'logged' => false,
                'answer' => 'Vous avez été déconnecté.'
            ]
        );
    }
}
