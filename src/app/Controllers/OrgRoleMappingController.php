<?php
namespace App\Controllers;

use App\Services\OrgRoleMappingService;

/**
 * Controller pour la gestion des mappings organisation → rôle
 * 
 * Fournit les endpoints REST pour la récupération des mappings
 * utilisés par le frontend pour l'attribution automatique de rôles
 * 
 * @author EHA Team
 * @since 2025-08-21
 */
class OrgRoleMappingController extends Controller
{
    /**
     * @var OrgRoleMappingService
     */
    private $orgRoleMappingService;
    
    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->orgRoleMappingService = new OrgRoleMappingService();
    }
    
    /**
     * GET /org-role-mappings
     * 
     * Récupère tous les mappings actifs pour le cache frontend
     * Optimisé pour la performance avec groupement par organisation
     * 
     * @return string JSON response
     */
    public function index()
    {
        try {
            // Récupération des mappings groupés par organisation
            $mappings = $this->orgRoleMappingService->getMappingsGroupedByOrganization();
            
            // Statistiques pour debug/monitoring
            $stats = $this->orgRoleMappingService->getMappingStats();
            
            return $this->response([
                'success' => true,
                'data' => $mappings,
                'meta' => [
                    'total_mappings' => $stats['total_mappings'],
                    'organizations_count' => $stats['organizations_count'],
                    'cache_timestamp' => time(),
                    'cache_duration' => 300 // 5 minutes recommandées
                ]
            ]);
            
        } catch (\Exception $e) {
            error_log("Erreur API org-role-mappings/index: " . $e->getMessage());
            
            return $this->response([
                'success' => false,
                'message' => 'Erreur lors de la récupération des mappings',
                'error_code' => 'MAPPING_FETCH_ERROR'
            ]);
        }
    }
    
    /**
     * GET /org-role-mappings/{orgCode}
     * 
     * Récupère les rôles à attribuer pour une organisation spécifique
     * Utilisé par le UsersService pour l'attribution dynamique
     * 
     * @param string $orgCode Code de l'organisation
     * @return string JSON response
     */
    public function getByOrganization(string $orgCode)
    {
        try {
            // Validation du code organisation
            if (!$this->orgRoleMappingService->isValidOrganizationCode($orgCode)) {
                return $this->response([
                    'success' => false,
                    'message' => 'Code organisation invalide',
                    'error_code' => 'INVALID_ORG_CODE'
                ]);
            }
            
            // Récupération des rôles pour cette organisation
            $roles = $this->orgRoleMappingService->getRolesForOrganization($orgCode);
            
            return $this->response([
                'success' => true,
                'data' => $roles,
                'meta' => [
                    'organization_code' => $orgCode,
                    'roles_count' => count($roles),
                    'has_mappings' => !empty($roles)
                ]
            ]);
            
        } catch (\Exception $e) {
            error_log("Erreur API org-role-mappings/{$orgCode}: " . $e->getMessage());
            
            return $this->response([
                'success' => false,
                'message' => 'Erreur lors de la récupération des rôles pour cette organisation',
                'error_code' => 'ORG_ROLES_FETCH_ERROR'
            ]);
        }
    }
}
