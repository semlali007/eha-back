<?php

namespace App\Security;

use App\Exceptions\AccessDeniedException;
use App\Security\User;
use App\Services\{
    UserService,
    HttpClientService as client
};
use Core\Cache\{
    ApcuCachePool as cache,
    Common\PhpCacheItem as CacheItem
} ;

class AuthorizationSecurity extends AbstractSecurity {

    private $accessToken;
    private $idToken;

    function checkUserAuthorization($roles) {
     
        if (isset($roles) && is_array($roles) && !empty($roles)) {

            if (getenv('WEBSSO') != true) {
                $user = UserService::getUser();
                if (!is_array($user) || !isset($user['roles']) || !is_array($user['roles'])) {
                    throw new AccessDeniedException();
                }

                $userRoles = $user['roles'];
                if (count(array_intersect($userRoles, $roles)) == 0) {
                    throw new AccessDeniedException();
                }
            } else {
                $headers = getallheaders();
                $authorization = explode(" ", $headers['authorization']);
                $this->idToken = $authorization[1];
                $this->accessToken = $authorization[2];

                if (null === $this->idToken) {
                    throw new AccessDeniedException('You must provide a valid token');
                }

                // 1- validation du token (decodeToken)
                $user = $this->getUser();
               
                // Vérifier les roles user (Appel Userinfo) => isMemberOf
                $userInfo = $this->getUserInfo();

                // Chercher dans isMemberOf le role necessaire 
                if (array_key_exists("isMemberOf", $userInfo)) {
                    $formatedRoles = $this->formatRoles($userInfo["isMemberOf"]); 
                    $user->addRoles($formatedRoles);
                }

                if (!$user->hasRole($roles)) {
                    throw new AccessDeniedException(); // retour a la frontend app 
                }
            }
        }
    }

    /**
     * Chercher les informations de l'utulisateur
     * @return mixed
     */
    public function getUserInfo() {
        // Chercher dans le cache les infos
        if (!cache::isExpired(User::INFOKEY.bin2hex(random_bytes(20)))) { // If not Expired  !!!!!! key not unique
            return cache::fetchObjectFromCache(User::INFOKEY);  // Retourner les infos depuis le cache
        } else {
            // Generer l'URL
            $userInfoUrl = getenv("URL_GARDIAN_WEBSSO") . getenv("PATH_USERINFO");
            $headers = [
                "Content-type:application/x-www-form-urlencoded",
                "Authorization: Bearer {$this->accessToken}"
            ];
            // Get user-info data    
            $infos = client::exec_post($userInfoUrl, [], $headers);
            cache::storeItemInCache(new CacheItem(User::INFOKEY, $infos));
            return $infos;
        }
    }

    /**
     * Vérifier si l'utilisateur existe ou non 
     * @return Object
     */
    public function getUser() {
        $decodedToken = (new jwtTokenService())->decodeToken($this->idToken);
        if (is_object($decodedToken)):
            return (new User())->setToken($this->idToken);
        endif;
        return false;
    }

    /**
     * 
     * @param array $params
     * @return array
     */
    public function formatRoles(array $params): array {

        return array_map(function($val) {
            $arrLine = explode(",", $val);
            if (is_array($arrLine)):
                $roleLine = explode("=", reset($arrLine));
                return (is_array($roleLine) && (count($roleLine) > 0)) ? $roleLine[1] : false;
            endif;
        }, $params);
    }

}
