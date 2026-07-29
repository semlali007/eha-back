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
        if (getenv('ENV') == 'locale') {
           
            $json = '{"gardianwebsso_cn":["LAMYA TIDJANI"],
                      "gardianwebsso_mail":["lamya.tidjani@testiam.edf.fr"],
                      "gardianwebsso_status":["A"],
                      "gardianwebsso_department":["\/EDF\/9651\/9803\/9708\/9708-3\/6217M\/621727\/62172702"],
                      "gardianwebsso_principal":["uid=G68670,ou=People,dc=gardiansesame"],
                      "gardianwebsso_ismemberof":["cn=EHA_ADMIN,ou=Groups,dc=gardiansesame", "cn=EHA_GESTIONNAIRE,ou=Groups,dc=gardiansesame"],
                      "gardianwebsso_groupid":["G68670"],
                      "gardianwebsso_sn":["TIDJANI"],
                      "gardianwebsso_id":["G68670"],
                      "gardianwebsso_nivauth":["1"],
                      "gardianwebsso_org":["62172702"],
                      "gardianwebsso_givenname":["LAMYA"],
                      "gardianwebsso_email":"lamya.tidjani@testiam.edf.fr"}';
            $attr = json_decode($json, true);
        } else {
            $attr =  $this->getSp()->getAttributes();
        }
                      //"gardianwebsso_ismemberof":["cn=EHA_ADMIN,ou=Groups,dc=gardiansesame", "cn=EHA_GESTIONNAIRE,ou=Groups,dc=gardiansesame"],

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

       // $this->getSp()->logout([
       //     'ReturnTo' => '/app/accueil',
        //]);

        //SimpleSAML_Session::getSessionFromRequest()->cleanup();
    }
}
