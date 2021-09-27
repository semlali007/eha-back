<?php

namespace App\Security;

class User {

    const INFOKEY = "USERINFO";

    /**
     * @var string
     */
    private $token;

    /**
     *
     * @var string
     */
    private string $nni;

    /**
     *
     * @var string
     */
    private string $fullname;

    /**
     * @var User Roles 
     */
    private $roles = [Role::DEFAULT];

    /**
     * 
     * @return string|null
     */
    public function getToken(): ?string {
        return $this->token;
    }

    /**
     * 
     * @param string $token
     * @return \self
     */
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
     * @return string
     */
    function getNni(): string {
        return $this->nni;
    }

    /**
     * 
     * @param string $nni
     * @return void
     */
    function setNni(string $nni): void {
        $this->nni = $nni;
    }
    
    /**
     * 
     * @return string
     */
    function getFullname(): string {
        return $this->fullname;
    }

    /**
     * 
     * @param string $fullname
     * @return void
     */
    function setFullname(string $fullname): void {
        $this->fullname = $fullname;
    }



}
