<?php

namespace App\Security;

use App\Exceptions\AccessDeniedException;
use App\Services\UserService;

class AuthorizationSecurity extends AbstractSecurity
{

    function checkUserAuthorization($roles)
    {
        if (isset($roles) && is_array($roles) && !empty($roles)) {
            $user = UserService::getUser();
            if (!is_array($user) || !isset($user['roles']) || !is_array($user['roles'])) {
                throw new AccessDeniedException();
            }

            $userRoles =  $user['roles'];
            if (count(array_intersect($userRoles, $roles)) == 0) {
                throw new AccessDeniedException();
            }
        }
    }
   
}