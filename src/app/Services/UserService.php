<?php

namespace App\Services;

use App\Security\{
    User,
    Role
};
use Core\Cache\{
    ApcuCachePool as cache,
    Common\PhpCacheItem as CacheItem
} ;

class UserService {


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
            $headers = ["Content-type:application/x-www-form-urlencoded", "Authorization: Bearer {$accessToken}"];
            // Get user-info data    
            $infos = HttpClientService::exec_post($userInfoUrl, [], $headers);
            cache::storeItemInCache(new CacheItem(User::INFOKEY, $infos));
            return $infos;
        }
    }

    /**
     * 
     * @param string $accessToken
     * @param User $user
     * @return object $user
     */
    public static function create(string $accessToken, User $user): User {
        $userInfo = self::getInfo($accessToken);

        // verifier si sub existe dans les userinfo
        if (array_key_exists("sub", $userInfo)) {
            $user->setNni($userInfo['sub']);
        }
        // verifier si sub existe dans les userinfo
        if (array_key_exists("cn", $userInfo)) {
            $user->setFullname($userInfo['cn']);
        }
        // Chercher dans isMemberOf le role necessaire 
        if (array_key_exists("isMemberOf", $userInfo)) {
            // Vérifier les roles user (Appel Userinfo) => isMemberOf
            $formatedRoles = Role::format($userInfo["isMemberOf"]);
            $user->addRoles($formatedRoles);
        }
        return $user;
    }

}
