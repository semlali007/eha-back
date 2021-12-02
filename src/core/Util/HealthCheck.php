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
        $ping = @fsockopen($serverUrl, $port, $errno, $errstr, self::PING_TIMEOUT);
        if ($ping) :
            fclose($ping);
            return true;
        endif;
        return false;
    }

    /**
     * Pinguer le serveur HLK pour retourner le Status du serveur si UP ou down 
     */
    public static function isActiveHlk():bool{
        return self::isServerEnable(getenv("HALK_API_BASE_URL")) ;
    } 

}
