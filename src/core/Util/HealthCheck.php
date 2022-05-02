<?php

namespace Core\Util;

use App\Services\HttpClientService as client ;

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

    /**
     * 
     * @param string $serverUrl
     * @param int $port
     * @return bool
     */
    public static function isServerEnable(string $serverUrl, int $port = 80): bool {
        return self::callAPI($serverUrl) ;
    }

    /**
     * Pinguer le serveur HLK pour retourner le Status du serveur si UP ou down 
     */
    public static function isActiveHlk():bool{
        return self::isServerEnable(getenv("HLK_API_BASE_URL")) ;
    } 
    
     /**
     * 
     * @param string $url
     * @return boolean
     */
    public static function callAPI(string $url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_exec($ch);
        $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($retcode > 0) ;
    }

}
