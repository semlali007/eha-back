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
        $this->sp = new Simple('default-sp');
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

            $userInfo =  $this->getSp()->getAttributes();

            if (is_array($userInfo) && isset($userInfo['gardianwebsso_id'][0]) &&  !empty($userInfo['gardianwebsso_id'][0])) {
                
                if (isset($userInfo["gardianwebsso_status"])) {
                    error_log("La valeur de gardianwebsso_status est : " . print_r($userInfo["gardianwebsso_status"], true));
                }
                
                if (isset($userInfo["gardianwebsso_status"])) {
                    if (in_array("P", $userInfo["gardianwebsso_status"])) {
                        $this->showAccessDeniedPage();
                    } else {
                        error_log("Type d'employé autorisé");
                    }
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
                // Traduction des roles de Standard to EHA_ROLES
                $roles = $this->translateRoles($this->roles);
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
     * Exemple: "/EDF/9651/9803/9708/9708-3/6217M/621727/62172702/62172702B" → "621727"
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
        
        // Prendre le segment à la position 6 (7ème élément)
        // Ex: /EDF/9651/9803/9708/9708-3/6217M/621727/62172702/62172702B → 621727
        if (count($parts) < 7) {
            return null;
        }
        
        $orgSegment = $parts[6];
        
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
