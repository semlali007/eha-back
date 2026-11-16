<?php

namespace App\Controllers;

use App\Exceptions\AccessDeniedException;
use App\Services\OrgRoleMappingService;
use App\Security\Role;

class RoleController extends Controller
{
    private $orgRoleMappingService;

    public function __construct($roles = [])
    {
        parent::__construct($roles);
        $this->orgRoleMappingService = new OrgRoleMappingService();
    }

    /**
     * Vérifie si l'utilisateur connecté a un rôle spécifique
     * Prend en compte les rôles SAML ET les rôles attribués par organisation
     * 
     * Utilisé par les guards Angular pour validation backend
     * 
     * @param string $roleName Nom du rôle à vérifier (ex: EHA_ADMIN, EHA_GESTIONNAIRE)
     * @return mixed JSON {hasRole: true/false, source: 'saml'|'organization'|'none'}
     */
    public function checkRole($roleName): mixed
    {
        try {
            $user = $this->getCurrentUser();
            
            if (!$user) {
                return $this->response([
                    'hasRole' => false,
                    'error' => 'User not authenticated',
                    'role_requested' => $roleName
                ]);
            }

            // 1. Vérifier les rôles SAML directs (gardianwebsso_ismemberof)
            $hasSamlRole = $user->hasRole([$roleName]);
            
            if ($hasSamlRole) {
                return $this->response([
                    'hasRole' => true,
                    'role' => $roleName,
                    'source' => 'saml',
                    'userRoles' => $user->getRoles(),
                    'nni' => $user->getNni()
                ]);
            }

            // 2. Vérifier les rôles attribués par organisation (OrgRoleMapping)
            $userOrg = $this->extractOrgCodeFromDepartment($user->getDirection());
            
            if ($userOrg) {
                // Récupérer les rôles mappés pour cette organisation
                $orgRoles = $this->orgRoleMappingService->getRolesForOrganization($userOrg);
                
                // Vérifier si le rôle demandé est dans les rôles de l'organisation
                foreach ($orgRoles as $orgRole) {
                    if ($orgRole['role_name'] === $roleName) {
                        return $this->response([
                            'hasRole' => true,
                            'role' => $roleName,
                            'source' => 'organization',
                            'organization_code' => $userOrg,
                            'mapping' => $orgRole,
                            'userRoles' => $user->getRoles(),
                            'nni' => $user->getNni()
                        ]);
                    }
                }
            }

            // 3. Rôle non trouvé
            return $this->response([
                'hasRole' => false,
                'role_requested' => $roleName,
                'user_saml_roles' => $user->getRoles(),
                'user_organization' => $userOrg ?? 'unknown',
                'organization_roles' => $orgRoles ?? [],
                'nni' => $user->getNni()
            ]);
            
        } catch (\Exception $e) {
            error_log("Erreur checkRole: " . $e->getMessage());
            
            return $this->response([
                'hasRole' => false,
                'error' => $e->getMessage(),
                'role_requested' => $roleName
            ]);
        }
    }
}
