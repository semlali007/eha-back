<?php

namespace App\Controllers;

use App\Models\External;
use App\Models\Administrator;
use App\Services\EntityService;
use Core\Http\Request;

class AuthenticationController extends Controller
{
    public function __construct($roles = [])
    {
        parent::__construct($roles);
    }

    /**
     * 
     * @return mixed
     */
    public function login(): mixed
    {
        $this->getSp()->requireAuth(['ReturnTo' => '/app/accueil']);

        header('Location: /app/accueil');
        exit;
        return $this->response($this->getSp()->getAttributes());
    }

    public function postLogin(): mixed
    {
        $attributes =  $this->getSp()->getAttributes();
        return $this->response($attributes);
    }

    public function postLogout(): mixed
    {
        $attributes =  $this->getSp()->getAttributes();
        return $this->response($attributes);
    }


    public function otherConnexion()
    {
        $url = $this->getSp()->getLoginURL();

        print('<a href="' . htmlspecialchars($url) . '">Login</a>');
    }

    public function attributes(): mixed
    {
        $attr =  $this->getSp()->getAttributes();
        return $this->response($attr);
    }

    public function logout()
    {

        $this->getSp()->logout([
            'ReturnTo' => '/app/accueil',
        ]);
        SimpleSAML_Session::getSessionFromRequest()->cleanup();
    }
}
