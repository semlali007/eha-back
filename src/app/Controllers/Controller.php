<?php

namespace App\Controllers;

use App\Exception\AccessDeniedException;
use App\Exceptions\InvalidParameterException;
use App\Security\AuthorizationSecurity;
use Database\DBConnection;
use Exception;

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
    public function __construct(DBConnection $db, $roles = [])
    {
        $this->db = $db;
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
        return json_encode($data);
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
     * @param $data
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
}
