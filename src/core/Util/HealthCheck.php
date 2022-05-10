<?php

namespace Core\Util;


/**
 * Description of HealthCheck
 *
 * @author HGDDEDBN
 */
class HealthCheck {
    
    const PING_TIMEOUT = 30 ;
    const HLK_ERROR_MSG = "HLK Non disponible" ;
    const HLK_SOURCE = "HLK" ;
    const SERVER_SOURCE = "SERVER" ;

    /**
     * 
     * @return bool
     */
    public static function checkDatabase(string $dbHost, int $port, string $dbname, string $dbuser, string $dbPass): bool {
        // Vérifier la connexion de la base 
        try {
            if (@pg_connect("host={$dbHost} port={$port} dbname={$dbname} user={$dbuser} password={$dbPass}")) {
                return true;
            }
        } catch (\Exception $e) {
           return false;
        }
        return false;
    }

}
