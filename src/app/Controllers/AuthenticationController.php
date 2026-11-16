<?php

namespace App\Controllers;

use App\Exceptions\AccessDeniedException;

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
            } else {
                // Fail-open, et volontairement bruyant. GARDIAN ne publie pas
                // gardianwebsso_status pour l'entityID CaaS
                // (https://eha-v2-preprod-caas.edf.fr/api/saml/metadata) : le SP
                // ne demande aucun RequestedAttribute, la diffusion est donc
                // pilotée entièrement côté IdP. Sans l'attribut, isset() est
                // faux et le filtre fournisseur était silencieusement ignoré -
                // un fournisseur entrait faute de preuve qu'il en est un.
                // À basculer en refus dès que l'IdP confirme la diffusion.
                error_log("ALERTE: gardianwebsso_status absent de l'assertion SAML - filtre fournisseur inopérant (accès accordé par défaut)");
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


    /**
     * GARDIAN does not send every attribute for every user. The REC
     * SECURINTERNET IdP omits gardianwebsso_mail entirely, and this method
     * used to dereference it unconditionally:
     *
     *     Undefined array key "gardianwebsso_mail"
     *     ... AuthenticationController.php on line 62
     *
     * The error handler turned that into an HTTP 200 whose body is a PHP
     * stack trace. The F5 passes it (it is not a 4XX/5XX), Angular cannot
     * parse it as JSON, decides the user is not authenticated, and sends a
     * fully authenticated user back to /login. Only gardianwebsso_status had
     * a guard; every other attribute was one missing value away from the same
     * failure.
     */
    public function attributes(): mixed
    {
        $raw = $this->getSp()->getAttributes();

        $first = static function (array $attributes, string $key): string {
            return isset($attributes[$key][0]) ? (string) $attributes[$key][0] : '';
        };

        $attr = $raw;
        $attr['gardianwebsso_groupid']    = $first($raw, 'gardianwebsso_groupid');
        $attr['gardianwebsso_id']         = $first($raw, 'gardianwebsso_id');
        $attr['gardianwebsso_cn']         = $first($raw, 'gardianwebsso_cn');
        $attr['gardianwebsso_department'] = $first($raw, 'gardianwebsso_department');
        $attr['gardianwebsso_email']      = $first($raw, 'gardianwebsso_mail');
        $attr['gardianwebsso_status']     = $first($raw, 'gardianwebsso_status');

        return $this->response($attr);
    }
    


    public function logout()
    {

        $this->getSp()->logout([
            'ReturnTo' => '/app/accueil',
        ]);

        // simplesamlphp 2.x dropped the 1.x underscore class aliases, so
        // SimpleSAML_Session no longer exists. Same call, namespaced class.
        \SimpleSAML\Session::getSessionFromRequest()->cleanup();
    }
}
