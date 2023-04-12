<?php

namespace App\Controllers;

use App\Controllers\Controller as baseController;
use Core\Util\HealthCheck as HCUtility;
use PDOException;

/**
 * Description of HealthcheckController
 *
 * @author HGDDEDBN
 */
class HealthcheckController extends baseController {

    /**
     * 
     * @return array
     */
    public function index()
    {
        try {
            $this->db->getPDO();
            $response = ["healthy" => true, "message" => "system is availble", "issues" => ["no issues"]];
        } catch (PDOException $e) {
            $response = [
                "healthy" => FALSE,
                "message" => "database {$_ENV["DB_NAME"]} unavailable - please contact your system administrator",
                "issues" => "UNAVAILABLE_DB"
            ];
        }

        return $this->response($response);
    }

}
