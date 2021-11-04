<?php

namespace App\Controllers;

use App\Models\External;
use App\Models\Administrator;
use App\Services\EntityService;
use Core\Http\Request ;

class AuthenticationController extends Controller
{
    public function __construct($roles = []) {
        parent::__construct($roles);
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
