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
        $attr = $this->attributes(); // Obtenir les attributs de l'utilisateur

        // Vérifier la valeur de gardianwebsso_status
        if ($attr['gardianwebsso_status'] === 'P') {
            // L'utilisateur n'est pas autorisé
            http_response_code(401); // Code de réponse HTTP pour non autorisé
            echo json_encode(['message' => 'Accès non autorisé']);
            exit;
        }

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
