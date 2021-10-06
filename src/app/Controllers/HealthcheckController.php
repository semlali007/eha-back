<?php

namespace App\Controllers;

use App\Controllers\Controller as baseController;
use Core\Util\HealthCheck as HCUtility;
use Core\Util\Json;

/**
 * Description of HealthcheckController
 *
 * @author HGDDEDBN
 */
class HealthcheckController extends baseController {

    /**
     * 
     * @return Json array
     */
    public function index() {

        // Vérifier la connexion a la base fonctionne ou non
        $checkDB = HCUtility::checkDatabase($_ENV["DB_HOST"], $_ENV["DB_PORT"], $_ENV["DB_NAME"], $_ENV["DB_USER"], $_ENV["DB_PWD"]);
        $response = ["healthy" => true, "message" => "system is availble", "issues" => ["no issues"]];
        if (!$checkDB):
            $response = [
                "healthy" => FALSE,
                "message" => "database {$_ENV["DB_NAME"]} unavailable - please contact your system administrator",
                "issues" => "UNAVAILABLE_DB"
            ];
        endif;
        return $this->response($response);
    }

}
