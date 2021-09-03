<?php

namespace App\Models;


class User extends Model
{
    /**
     * @var string
     */
    private $token;

    /***
     * @var string
     */
    private $profile;

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getRoles(): array
    {
        return [];
    }

    public function getProfile(): ?string
    {
        return $this->profile;
    }

    public function setProfile(string $profile): self
    {
        $this->profile = $profile;

        return $this;
    }

}
