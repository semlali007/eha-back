<?php

namespace App\Security;

use App\Exceptions\AccessDeniedException;
use App\Security\User;
use Core\Cache\ApcuCachePool as Pool ;
use Core\Http\Response ;
use App\Services\{
    UserService,
    JwtTokenService
};

class AuthorizationSecurity extends AbstractSecurity {

    private $accessToken;
    private $idToken;

    function checkUserAuthorization($roles =[]) {

        if (isset($roles) && is_array($roles) && !empty($roles)) { 
                $headers = array_change_key_case(getallheaders(),CASE_LOWER) ;
                $authorization = explode(" ", $headers['authorization']);
                $this->idToken = $authorization[1];
                $this->accessToken = $authorization[2];

                if (null === $this->idToken) {
                    throw new AccessDeniedException('You must provide a valid token');
                }
                // 1- recupérer l'objet user
                $user = UserService::create($this->accessToken, $this->getUser());
                // Traduction des roles de Standard to EHA_ROLES
                $roles = $this->translateRoles($roles);
                if (!$user->hasRole($roles)) {
                    Pool::clearAllObjectsFromCache();
                    http_response_code(Response::HTTP_UNAUTHORIZED);
                    throw new AccessDeniedException(); // retour a la frontend app 
                 
            }
            return $user ;
        }
    }

    /**
     * Vérifier si l'utilisateur existe ou non 
     * @return Object
     */
     public function getUser() {
        $decodedToken = (new JwtTokenService())->decodeToken($this->idToken);
        $user = (new User())->setToken($this->idToken);
        if (is_object($decodedToken)&&property_exists($decodedToken, "sub")):
            $user->setNni($decodedToken->sub);
        endif;
        return $user;
    }

    /**
     * 
     * @param array $roles
     * @return array
     */
    public function translateRoles(array $roles): array {

        $input = array_fill_keys(array_values($roles), "");
        return array_values(array_replace(
                        $input, array_intersect_key(
                                $GLOBALS['configuration']['roles'], $input
        )));
    }

}
