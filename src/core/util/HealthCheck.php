<?php

namespace Core\Util;

/**
 * Description of HealthCheck
 *
 * @author HGDDEDBN
 */
class HealthCheck {

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
        } catch (Exception $e) {
           echo $e->getMessage();
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

        $ping = @fsockopen($serverUrl, $port, $errno, $errstr, 10);
        if ($ping) {
            @fclose($ping);
            return true;
        }
        return false;
    }

}
