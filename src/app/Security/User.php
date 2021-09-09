<?php

namespace App\Security;

class User {

    const INFOKEY = "USERINFO";

    /**
     * @var string
     */
    private $token;

    /**
     * @var User Roles 
     */
    private $roles = [Role::DEFAULT];

    /**
     * @var string
     */
    private $profile;

    public function getToken(): ?string {
        return $this->token;
    }

    public function setToken(string $token): self {
        $this->token = $token;

        return $this;
    }

    public function getRoles(): array {
        return $this->roles;
    }

    /**
     * 
     * @param array $roles
     */
    public function addRoles(array $roles = []): self {

        $this->roles = array_merge($this->roles, $roles);
        return $this;
    }

    /**
     * if has role 
     * $this->roles => roles de l'utilisateur
     * @param type $roles => recu par le routing.php
     */
    public function hasRole(array $roles = []): bool {
        if (is_array($roles)) {
            $intersect = array_intersect($this->roles, $roles);
            return (count($intersect) > 0);
        }
        return false;
    }

    /**
     * 
     * @return string|null
     */
    public function getProfile(): ?string {
        return $this->profile;
    }

    public function setProfile(string $profile): self {
        $this->profile = $profile;

        return $this;
    }

}
