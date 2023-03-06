<?php

namespace App\Controllers;


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
