<?php
namespace App\Services;

use App\Repository\OrgRoleMappingRepository;

/**
 * Service de gestion des mappings organisation → rôle
 * 
 * Couche métier pour l'attribution automatique de rôles
 * basée sur l'organisation de l'utilisateur
 * 
 * @author EHA Team
 * @since 2025-08-21
 */
class OrgRoleMappingService
{
    /**
     * @var OrgRoleMappingRepository
     */
    private $orgRoleMappingRepository;
    
    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->orgRoleMappingRepository = new OrgRoleMappingRepository();
    }
    
    /**
     * Récupère les rôles à ajouter pour une organisation donnée
     * 
     * @param string $organizationCode Code de l'organisation
     * @return array Liste des rôles à attribuer
     */
    public function getRolesForOrganization(string $organizationCode): array
    {
        if (empty($organizationCode)) {
            return [];
        }
        
        return $this->orgRoleMappingRepository->getRolesByOrganization($organizationCode);
    }
    
    /**
     * Récupère tous les mappings pour le cache frontend
     * 
     * @return array Liste complète des mappings actifs
     */
    public function getAllMappings(): array
    {
        return $this->orgRoleMappingRepository->getAllActiveMappings();
    }
    
    /**
     * Formate les mappings pour l'API (optimisation frontend)
     * 
     * @return array Mappings formatés par organisation
     */
    public function getMappingsGroupedByOrganization(): array
    {
        $mappings = $this->getAllMappings();
        $grouped = [];
        
        foreach ($mappings as $mapping) {
            $orgCode = $mapping['organization_code'];
            
            if (!isset($grouped[$orgCode])) {
                $grouped[$orgCode] = [];
            }
            
            $grouped[$orgCode][] = [
                'role_name' => $mapping['role_name'],
                'role_dc' => $mapping['role_dc'],
                'role_ou' => $mapping['role_ou'],
                'description' => $mapping['description']
            ];
        }
        
        return $grouped;
    }
    
    /**
     * Vérifie si une organisation a des mappings configurés
     * 
     * @param string $organizationCode Code organisation
     * @return bool True si des mappings existent
     */
    public function hasOrganizationMappings(string $organizationCode): bool
    {
        $roles = $this->getRolesForOrganization($organizationCode);
        return !empty($roles);
    }
    
    /**
     * Retourne les statistiques des mappings
     * 
     * @return array Statistiques (nombre total, organisations couvertes, etc.)
     */
    public function getMappingStats(): array
    {
        $mappings = $this->getAllMappings();
        
        $organizations = array_unique(array_column($mappings, 'organization_code'));
        $roles = array_unique(array_column($mappings, 'role_name'));
        
        return [
            'total_mappings' => count($mappings),
            'organizations_count' => count($organizations),
            'unique_roles' => count($roles),
            'organizations' => $organizations,
            'roles' => $roles
        ];
    }
    
    /**
     * Valide un code organisation (format attendu)
     * 
     * @param string $organizationCode Code à valider
     * @return bool True si le format est valide
     */
    public function isValidOrganizationCode(string $organizationCode): bool
    {
        // Format attendu: numérique, 6-10 chiffres
        return preg_match('/^\d{6,10}$/', $organizationCode);
    }
}
