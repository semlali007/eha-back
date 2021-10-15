<?php

namespace App\Security;

use App\Exceptions\AccessDeniedException;
use App\Security\{User,Role};
use App\Services\{UserService,JwtTokenService};

class AuthorizationSecurity extends AbstractSecurity {

    private $accessToken;
    private $idToken;

    function checkUserAuthorization(array $roles =[]) {
        
        $headers = getallheaders();
        $authorization = explode(" ", $headers['authorization']);
        $this->idToken = $authorization[1];
        $this->accessToken = $authorization[2];

        if (null === $this->idToken) {
            throw new AccessDeniedException('You must provide a valid token');
        }
        // 1- recupérer l'objet user
        $user = UserService::create($this->accessToken, $this->getUser());
        $roles = $this->translateRoles($roles); // 2- Traduction des roles de Standard to EHA_ROLES
        
        if (!$user->hasRole($roles)) {
            http_response_code(\Core\Http\Response::HTTP_UNAUTHORIZED);
            throw new AccessDeniedException(); // retour a la frontend app 
        }     
        return $user;
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
    public function translateRoles(array $roles = []): array {
        
        $input = array_fill_keys(array_values((empty($roles)) ? [Role::DEFAULT] : $roles), "");
        return array_values(array_replace(
                        $input, array_intersect_key(
                                $GLOBALS['configuration']['roles'], $input
        )));
    }

}
