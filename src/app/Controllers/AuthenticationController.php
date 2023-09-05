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
        $state = str_replace('w_t_p', '/', $state);

        $userInfo =  $this->getSp()->getAttributes();
            //var_dump($userInfo);exit;
        if (is_array($userInfo)) {
            if (isset($userInfo["gardianwebsso_status"])) {
                if (in_array("P", $userInfo["gardianwebsso_status"])) {
                    $this->showAccessDeniedPage();
                } else {
                    error_log("Type d'employé autorisé");
                }
            }
                
        } else {
                throw new AccessDeniedException('You must authenticate');
        }
        // // Vérifier la valeur de gardianwebsso_status
        // if ($attr['gardianwebsso_status'] === 'P') {
        //     // L'utilisateur n'est pas autorisé
        //     http_response_code(401); // Code de réponse HTTP pour non autorisé
        //     echo json_encode(['message' => 'Accès non autorisé']);
        //     exit;
        // }

        $this->getSp()->requireAuth(['ReturnTo' => $state]);
        header('Location: '. $state);
    }

    // public function login($state = '/app/accueil'): mixed
    // {
    //     $state = str_replace('w_t_p', '/', $state);
    //     $this->getSp()->requireAuth(['ReturnTo' => $state]);
    //     header('Location: '. $state); 
    // }


    public function attributes(): mixed
    {
        $attr =  $this->getSp()->getAttributes();
        $attr['gardianwebsso_groupid'] = $attr['gardianwebsso_groupid'][0];
        $attr['gardianwebsso_id'] = $attr['gardianwebsso_id'][0];
        $attr['gardianwebsso_cn'] = $attr['gardianwebsso_cn'][0];
        $attr['gardianwebsso_department'] = $attr['gardianwebsso_department'][0];
        $attr['gardianwebsso_email'] = $attr['gardianwebsso_mail'][0];
        // $attr['gardianwebsso_status'] = $attr['gardianwebsso_status'][0];
        $attr['gardianwebsso_status'] = isset($attr['gardianwebsso_status'][0]) ? $attr['gardianwebsso_status'][0] : '';
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
