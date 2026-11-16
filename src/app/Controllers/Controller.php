<?php

namespace App\Controllers;

use App\Exceptions\InvalidParameterException;
use App\Security\AuthorizationSecurity;
use Core\Database\DBConnection;
use Core\Util\HealthCheck as health;
use Core\Http\Response;
use Exception;
use SimpleSAML\{Auth\Simple, Session};
use App\Security\Role;
use App\Security\User as SecurityUser;
use App\Exceptions\AccessDeniedException;
use App\Services\OrgRoleMappingService;

abstract class Controller
{
    /**
     * @var DBConnection
     */
    protected $db;
    /**
     * @var array|mixed
     */
    protected $roles;

    protected $currentUser;

    protected $sp;

    /**
     * Controller constructor.
     * @param DBConnection $db
     * @param array $roles
     */
    public function __construct($roles = [])
    {
        $this->db = DBConnection::getInstance();
        $this->roles = $roles;
        //$this->sp = new Simple('default-sp');
        $this->checkUserAuthorization($roles);
    }

    /**
     * @return DBConnection
     */

    protected function getDB()
    {
        return $this->db;
    }


    protected function getSp()
    {
        return $this->sp;
    }

    /**
     * @param $data
     */
    protected function response($data)
    {
        return json_encode($data, JSON_INVALID_UTF8_IGNORE);
    }

    /**
     * @param $json
     * @return array|mixed
     */
    protected function decode($json)
    {
        return json_decode($json, true) ?? [];
    }

    protected function showAccessDeniedPage()
    {
        //throw new AccessDeniedException();
        header("HTTP/1.1 403 Forbidden");
        echo "Accès non autorisé";
        exit;
    }

    /**
     *
     */
    protected function checkUserAuthorization()
    {
        if (is_array($this->roles) && !empty($this->roles)) {

            if (getenv('ENV') == 'locale') {
                //$json = '{"gardianwebsso_groupid":["G68670"],"gardianwebsso_sn":["CEDRIC"],"gardianwebsso_id":["G68670"],"gardianwebsso_cn":["CEDRIC SIMOU NONO"],"gardianwebsso_mail":["cedric-externe.simou-nono@edf.fr"],"gardianwebsso_nivauth":["1"],"gardianwebsso_department":["\/EDF\/9651\/9803\/9708\/9708-3\/6217M\/621727\/62172702"],"gardianwebsso_profil":["CAM_CONSTAT LIMITE SD-EXT_6217M","CAM_CONSTAT EMETTEUR SD-EXT_6217M","CHIFFREMENTDONNEES_CD_62172704","CAM_CONSTAT REAL VISITE-EXT_6217M","VPN ADMIN_ASTREINTE-ADMIN","98S_EMETTEUR-AUTONOME_62172704"],"gardianwebsso_ismemberof":["cn=EHA_ADMIN,ou=Groups,dc=gardiansesame","cn=EHA_GESTIONNAIRE,ou=Groups,dc=gardiansesame"],"gardianwebsso_principal":["uid=G68670,ou=People,dc=gardiansesame"],"gardianwebsso_org":["62172704"],"gardianwebsso_givenname":["CEDRIC"],"gardianwebsso_email":"cedric-externe.simou-nono@edf.fr"}';
                $json = '{"gardianwebsso_cn":["LAMYA TIDJANI"],
"gardianwebsso_mail":["lamya.tidjani@testiam.edf.fr"],
"gardianwebsso_status":["A"],
"gardianwebsso_department":["\/EDF\/9651\/9803\/9708\/9708-3\/6217M\/621727\/62172702"],
"gardianwebsso_principal":["uid=G68670,ou=People,dc=gardiansesame"],
"gardianwebsso_groupid":["G68670"],
"gardianwebsso_sn":["TIDJANI"],
"gardianwebsso_id":["G68670"],
"gardianwebsso_nivauth":["1"],
"gardianwebsso_ismemberof":["cn=EHA_ADMIN,ou=Groups,dc=gardiansesame", "cn=EHA_GESTIONNAIRE,ou=Groups,dc=gardiansesame"],
"gardianwebsso_org":["62172702"],
"gardianwebsso_givenname":["LAMYA"],
"gardianwebsso_email":"lamya.tidjani@testiam.edf.fr"}';


// "gardianwebsso_ismemberof":["cn=EHA_ADMIN,ou=Groups,dc=gardiansesame"],
// "gardianwebsso_ismemberof":["cn=EHA_GESTIONNAIRE,ou=Groups,dc=gardiansesame"],
// "gardianwebsso_ismemberof":[],


       $userInfo = json_decode($json, true);

            } else {
                $userInfo =  $this->getSp()->getAttributes();
            }
            //var_dump($userInfo);exit;
            if (is_array($userInfo) && isset($userInfo['gardianwebsso_id'][0]) &&  !empty($userInfo['gardianwebsso_id'][0])) {
                
                // Filtre des utilisateurs fournisseurs.
                //
                // Fail-open, et volontairement bruyant : voir le commentaire
                // détaillé dans AuthenticationController::login(). GARDIAN ne
                // diffuse pas gardianwebsso_status à l'entityID CaaS, donc
                // isset() est faux et ce filtre ne s'applique à personne. Le
                // cas manquant était auparavant muet, ce qui a masqué le trou.
                // À basculer en refus dès que l'IdP confirme la diffusion.
                if (isset($userInfo["gardianwebsso_status"])) {
                    error_log("La valeur de gardianwebsso_status est : " . print_r($userInfo["gardianwebsso_status"], true));
                    if (in_array("P", $userInfo["gardianwebsso_status"])) {
                        $this->showAccessDeniedPage();
                    } else {
                        error_log("Type d'employé autorisé");
                    }
                } else {
                    error_log("ALERTE: gardianwebsso_status absent de l'assertion SAML - filtre fournisseur inopérant (accès accordé par défaut)");
                }

                $user = new SecurityUser();
                if (array_key_exists("gardianwebsso_id", $userInfo)) {
                    $user->setNni($userInfo['gardianwebsso_id'][0]);
                }
                // verifier si sub existe dans les userinfo
                if (array_key_exists("gardianwebsso_cn", $userInfo)) {
                    $user->setFullname($userInfo['gardianwebsso_cn'][0]);
                }
                // verifier si sub existe dans les userinfo
                if (array_key_exists("gardianwebsso_department", $userInfo)) {
                    $user->setDirection($userInfo['gardianwebsso_department'][0]);
                }
                // Chercher dans isMemberOf le role necessaire 
                if (array_key_exists("gardianwebsso_ismemberof", $userInfo)) {
                    // Vérifier les roles user (Appel Userinfo) => isMemberOf
                    $formatedRoles = Role::format($userInfo["gardianwebsso_ismemberof"]);
                    $user->addRoles($formatedRoles);
                }

                // Enrichir avec les rôles d'organisation (mapping)
                if (array_key_exists("gardianwebsso_department", $userInfo)) {
                    $orgCode = $this->extractOrgCodeFromDepartment($userInfo['gardianwebsso_department'][0]);
                    if ($orgCode) {
                        $orgRoleMappingService = new OrgRoleMappingService();
                        $orgRoles = $orgRoleMappingService->getRolesForOrganization($orgCode);
                        if (!empty($orgRoles)) {
                            // Formater les rôles d'organisation au même format que les rôles SAML
                            foreach ($orgRoles as $orgRole) {
                                $roleString = "cn={$orgRole['role_name']},ou={$orgRole['role_ou']},dc={$orgRole['role_dc']}";
                                $formatedOrgRoles = Role::format([$roleString]);
                                $user->addRoles($formatedOrgRoles);
                            }
                        }
                    }
                }

                $user->setEmail(getenv("DEFAULT_SEND_EMAIL"));
                if (array_key_exists("gardianwebsso_mail", $userInfo) && !empty($userInfo['gardianwebsso_mail'])) {
                    $user->setEmail($userInfo['gardianwebsso_mail'][0]);
                }
                //var_dump($this->roles);
                // Traduction des roles de Standard to EHA_ROLES
                $roles = $this->translateRoles($this->roles);
                //var_dump($roles);
                if (!$user->hasRole($roles)) {
                    throw new AccessDeniedException(); // retour a la frontend app 
                }

                $this->currentUser = $user;
            } else {
                throw new AccessDeniedException('You must authenticate');
            }
        }
    }

    public function translateRoles(array $roles): array
    {
        $input = array_fill_keys(array_values($roles), "");
        return array_values(array_replace(
            $input,
            array_intersect_key(
                $GLOBALS['configuration']['roles'],
                $input
            )
        ));
    }

    /**
     * Extrait le code organisation depuis le department SAML
     * 
     * Exemple: "/EDF/9651/9803/9708/9708-3/6217M/621727/62172702" → "621727"
     * 
     * @param string $department Chemin complet du department
     * @return string|null Code organisation (avant-dernier segment)
     */
    protected function extractOrgCodeFromDepartment(string $department): ?string
    {
        if (empty($department)) {
            return null;
        }

        // Supprimer les slashs de début/fin et découper
        $parts = explode('/', trim($department, '/'));
        
        // Vérifier qu'il y a au moins 2 segments
        if (count($parts) < 2) {
            return null;
        }
        
        // Prendre l'avant-dernier segment
        $orgSegment = $parts[count($parts) - 2];
        
        // Vérifier que c'est un code valide (chiffres uniquement ou alphanumériques)
        if (preg_match('/^[0-9A-Z]+$/i', $orgSegment)) {
            return $orgSegment;
        }
        
        return null;
    }
    /**
     * @param Exception
     */

    protected function error(Exception $ex)
    {
        $errors = [];
        if ($ex instanceof InvalidParameterException) {
            $errors = $ex->getErrors();
        }
        return  json_encode([
            'code' => $ex->getCode(),
            'message' => $ex->getMessage(),
            'errors' => $errors
        ]);
    }

    public function required(string $name, $data, $message = "")
    {
        if (!isset($data[$name])) {
            throw (new InvalidParameterException())->setErrors([$message]);
        }

        $value = trim($data[$name]);
        if (is_null($value) || empty($value)) {
            throw (new InvalidParameterException())->setErrors([$message]);
        }
    }
    /**
     * 
     * @return mixed
     */
    function getCurrentUser(): mixed
    {
        return $this->currentUser;
    }

    /**
     * 
     * @return mixed
     */
    public function hlkErrorResponse(): mixed
    {
        http_response_code(Response::HTTP_SERVICE_UNAVAILABLE);
        return $this->response([
            "source" => health::HLK_SOURCE,
            "error" => health::HLK_ERROR_MSG,
            "status" => Response::HTTP_SERVICE_UNAVAILABLE
        ]);
    }
}
