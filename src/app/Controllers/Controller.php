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
        //$this->sp = new Simple('default-sp') ;
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

    /**
     *
     */
    protected function checkUserAuthorization()
    {
        //var_dump($this->roles);exit;

        if (is_array($this->roles) && !empty($this->roles)) {

            $userInfo =  $this->getSp()->getAttributes();
            //var_dump($userInfo);exit;
            if (is_array($userInfo) && isset($userInfo['gardianwebsso_id'][0]) &&  !empty($userInfo['gardianwebsso_id'][0])) {
                //var_dump($userInfo);exit;
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

                $user->setEmail(getenv("DEFAULT_SEND_EMAIL"));
                if (array_key_exists("email", $userInfo) && !empty($userInfo['email'])) {
                    $user->setEmail($userInfo['email'][0]);
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
