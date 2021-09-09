<?php

namespace App\Services;

use App\Security\User;
use Core\Cache\{
    ApcuCachePool as cache,
    Common\PhpCacheItem as CacheItem
} ;

class UserService {

    static function getUser() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['user'])) {
            return $_SESSION['user'];
        } else {
            return null;
        }
    }

    /**
     * Chercher les informations de l'utilisateur
     * @return mixed
     */
    static function getInfo(string $accessToken) {
        // Chercher dans le cache les infos
        if (!cache::isExpired(User::INFOKEY . bin2hex(random_bytes(20)))) { // If not Expired  !!!!!! key not unique
            return cache::fetchObjectFromCache(User::INFOKEY);  // Retourner les infos depuis le cache
        } else {
            // Generer l'URL
            $userInfoUrl = getenv("URL_GARDIAN_WEBSSO") . getenv("PATH_USERINFO");
            $headers = [ "Content-type:application/x-www-form-urlencoded", "Authorization: Bearer {$accessToken}"];
            // Get user-info data    
            $infos = HttpClientService::exec_post($userInfoUrl, [], $headers);
            cache::storeItemInCache(new CacheItem(User::INFOKEY, $infos));
            return $infos;
        }
    }
   
}
