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
    static function getInfo(string $accessToken,User $user) {
        $cacheName = User::INFOKEY .$user->getNni() ;
        // Chercher dans le cache les infos
        if (!cache::isExpired($cacheName)) { // If not Expired  !!!!!! key not unique
            return cache::fetchObjectFromCache($cacheName);  // Retourner les infos depuis le cache
        } else {
            // Generer l'URL
            $userInfoUrl = getenv("URL_GARDIAN_WEBSSO") . getenv("PATH_USERINFO");
            $headers = ["Content-type:application/x-www-form-urlencoded", "Authorization: Bearer {$accessToken}"];
            // Get user-info data    
            $infos = HttpClientService::exec_post($userInfoUrl, [], $headers);
            cache::storeItemInCache(new CacheItem($cacheName, $infos));
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
        $userInfo = self::getInfo($accessToken,$user);

        // verifier si sub existe dans les userinfo
        if (array_key_exists("sub", $userInfo)&& is_null($user->getNni())) {
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
