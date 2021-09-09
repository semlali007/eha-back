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
                // 1- recupérer l'objet user
                $user = $this->getUser();
                // Vérifier les roles user (Appel Userinfo) => isMemberOf
                $userInfo = UserService::getInfo($this->accessToken);

                // Chercher dans isMemberOf le role necessaire 
                if (array_key_exists("isMemberOf", $userInfo)) {
                    $formatedRoles = Role::format($userInfo["isMemberOf"]);
                    $user->addRoles($formatedRoles);
                }

                if (!$user->hasRole($roles)) {
                    http_response_code(\Core\Http\Response::HTTP_UNAUTHORIZED);
                    throw new AccessDeniedException(); // retour a la frontend app 
                }
            }
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
        return new User();
    }

}
