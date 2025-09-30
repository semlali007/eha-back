<?php

namespace App\Services;

use Exception;

class AuthService
{
    private $validUsers;

    public function __construct()
    {
        // Récupérer les utilisateurs autorisés depuis les variables d'environnement
        $usersEnv = getenv('COCKPIT_API_USERS');
        $this->validUsers = [];
        
        if ($usersEnv) {
            // Format attendu: "user1:pass1,user2:pass2,user3:pass3"
            $userPairs = explode(',', $usersEnv);
            foreach ($userPairs as $pair) {
                $userPass = explode(':', trim($pair));
                if (count($userPass) === 2) {
                    $this->validUsers[trim($userPass[0])] = trim($userPass[1]);
                }
            }
        }
    }

    /**
     * Authentifie un utilisateur avec username/password
     */
    public function authenticate($username, $password)
    {
        if (empty($this->validUsers)) {
            throw new Exception('Aucun utilisateur configuré dans COCKPIT_API_USERS');
        }

        if (!isset($this->validUsers[$username])) {
            return false;
        }

        return hash_equals($this->validUsers[$username], $password);
    }

    /**
     * Récupère les informations d'un utilisateur
     */
    public function getUserInfo($username)
    {
        if (!isset($this->validUsers[$username])) {
            return null;
        }

        // Mapper les utilisateurs aux NNI - vous pouvez ajuster cette logique
        $userNniMapping = $this->getUserNniMapping();
        
        return [
            'username' => $username,
            'user_id' => $username,
            'user_nni' => $userNniMapping[$username] ?? $username,
            'user_full_name' => $this->getFullName($username)
        ];
    }

    /**
     * Mapping des utilisateurs vers les NNI
     * À personnaliser selon vos besoins
     */
    private function getUserNniMapping()
    {
        $mappingEnv = getenv('COCKPIT_API_USER_NNI_MAPPING');
        $mapping = [];
        
        if ($mappingEnv) {
            // Format: "user1:G12345,user2:A67890"
            $pairs = explode(',', $mappingEnv);
            foreach ($pairs as $pair) {
                $userNni = explode(':', trim($pair));
                if (count($userNni) === 2) {
                    $mapping[trim($userNni[0])] = trim($userNni[1]);
                }
            }
        }
        
        return $mapping;
    }

    /**
     * Récupère le nom complet d'un utilisateur
     */
    private function getFullName($username)
    {
        $fullNameMappingEnv = getenv('COCKPIT_API_USER_FULLNAME_MAPPING');
        $mapping = [];
        
        if ($fullNameMappingEnv) {
            // Format: "user1:John Doe,user2:Jane Smith"
            $pairs = explode(',', $fullNameMappingEnv);
            foreach ($pairs as $pair) {
                $userFullName = explode(':', trim($pair), 2);
                if (count($userFullName) === 2) {
                    $mapping[trim($userFullName[0])] = trim($userFullName[1]);
                }
            }
        }
        
        return $mapping[$username] ?? $username;
    }
}