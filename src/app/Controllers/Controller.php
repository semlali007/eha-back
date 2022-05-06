<?php

namespace App\Controllers;

use App\Exceptions\{AccessDeniedException,InvalidParameterException };
use App\Security\AuthorizationSecurity;
use Core\Database\DBConnection;
use Core\Util\HealthCheck as health ;
use Core\Http\Response ;
use Exception;
use App\Repository\NotificationsRepository ;
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
    
    protected $currentUser ;

    /**
     * Controller constructor.
     * @param DBConnection $db
     * @param array $roles
     */
    public function __construct($roles = [])
    {
        $this->db = DBConnection::getInstance();
        $this->roles = $roles;
        $this->checkUserAuthorization($roles);
    }

    /**
     * @return DBConnection
     */

    protected function getDB()
    {
        return $this->db;
    }

    /**
     * @param $data
     */
    protected function response($data)
    {   
        return json_encode($data,JSON_INVALID_UTF8_IGNORE);
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
            $authorization = new AuthorizationSecurity();
            $this->currentUser = $authorization->checkUserAuthorization($this->roles);
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
    function getCurrentUser(): mixed {
        return $this->currentUser;
    }
    
    /**
     * 
     * @return mixed
     */
     public function hlkErrorResponse():mixed {
        http_response_code(Response::HTTP_SERVICE_UNAVAILABLE);
        return $this->response([
            "source" => health::HLK_SOURCE,
            "error" => health::HLK_ERROR_MSG,
            "status" => Response::HTTP_SERVICE_UNAVAILABLE
        ]);
    }
}