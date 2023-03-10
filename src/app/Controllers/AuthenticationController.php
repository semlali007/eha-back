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

    public function login($state = '/app/accueil'): mixed
    {
        $url = urldecode($state);
        $this->getSp()->requireAuth(['ReturnTo' => $url]);
        header('Location: ' . $url);
    }


    public function attributes(): mixed
    {
        $attr =  $this->getSp()->getAttributes();
        $attr['gardianwebsso_groupid'] = $attr['gardianwebsso_groupid'][0];
        $attr['gardianwebsso_id'] = $attr['gardianwebsso_id'][0];
        $attr['gardianwebsso_cn'] = $attr['gardianwebsso_cn'][0];
        $attr['gardianwebsso_department'] = $attr['gardianwebsso_department'][0];
        $attr['gardianwebsso_email'] = $attr['gardianwebsso_mail'][0];
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
