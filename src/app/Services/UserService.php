<?php

namespace App\Services;

use App\Security\{
    User,
    Role
};
use Core\Http\Response ;
use App\Exceptions\AccessDeniedException;
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
            $infos= cache::fetchObjectFromCache($cacheName);  // Retourner les infos depuis le cache
            (is_array($infos)&& !array_key_exists("last_acted_on", $infos)) ? $infos['last_acted_on'] = time() : $infos;
        } else {
            // Generer l'URL
            $userInfoUrl = getenv("URL_GARDIAN_WEBSSO") . getenv("PATH_USERINFO");
            $headers = ["Content-type:application/x-www-form-urlencoded", "Authorization: Bearer {$accessToken}"];
            // Get user-info data    
            $infos = HttpClientService::exec_post($userInfoUrl, [], $headers);
            (is_array($infos)&& !array_key_exists("last_acted_on", $infos)) ? $infos['last_acted_on'] = time() : $infos;
            cache::storeItemInCache(new CacheItem($cacheName, $infos));
        }
        return $infos;
    }

    /**
     * 
     * @param string $accessToken
     * @param User $user
     * @return object $user
     */
    public static function create(string $accessToken, User $user): User {
        $userInfo = self::getInfo($accessToken,$user);
        self::checkSessionIfExpired($userInfo,$user); // session expire apres temps paramétrés
        
        // verifier si sub existe dans les userinfo
        if (array_key_exists("sub", $userInfo)&& is_null($user->getNni())) {
            $user->setNni($userInfo['sub']);
        }
        // verifier si sub existe dans les userinfo
        if (array_key_exists("cn", $userInfo)) {
            $user->setFullname($userInfo['cn']);
        }
        // verifier si sub existe dans les userinfo
        if (array_key_exists("gardianHierarchie", $userInfo)) {
            $user->setDirection($userInfo['gardianHierarchie']);
        }
        // Chercher dans isMemberOf le role necessaire 
        if (array_key_exists("isMemberOf", $userInfo)) {
            // Vérifier les roles user (Appel Userinfo) => isMemberOf
            $formatedRoles = Role::format($userInfo["isMemberOf"]);
            $user->addRoles($formatedRoles);
        }
        return $user;
    }
    
   /**
    * 
    * @param mixed $userInfo
    * @param User $user
    * @return void
    * @throws AccessDeniedException
    */
    static function checkSessionIfExpired(mixed $userInfo, User $user): void {
        
        if (array_key_exists('last_acted_on', $userInfo) && ($userInfo['last_acted_on'] <= time() - intval(getenv("EXPIRE_AFTER")))) :
            unset($userInfo['last_acted_on']);
            cache::storeItemInCache(new CacheItem(User::INFOKEY . $user->getNni(), $userInfo));
            throw new AccessDeniedException();
        else :
            $userInfo['last_acted_on'] = time();
            cache::storeItemInCache(new CacheItem(User::INFOKEY . $user->getNni(), $userInfo));
        endif;
    }
}
