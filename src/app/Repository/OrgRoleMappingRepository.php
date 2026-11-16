<?php
namespace App\Repository;

use App\Models\OrgRoleMapping;

/**
 * Repository pour la gestion des mappings organisation → rôle
 * 
 * Couche d'accès aux données pour les mappings automatiques
 * de rôles basés sur l'organisation utilisateur
 * 
 * @author EHA Team
 * @since 2025-08-21
 */
class OrgRoleMappingRepository extends AbstractRepository
{
    private $tableName = 'org_role_mapping';
    
    /**
     * Récupère les rôles à attribuer pour une organisation donnée
     * 
     * @param string $organizationCode Code de l'organisation
     * @return array Liste des mappings actifs pour cette organisation
     */
    public function getRolesByOrganization(string $organizationCode): array
    {
        try {
            $sql = "SELECT * FROM {$this->tableName} 
                    WHERE organization_code = :org_code 
                    AND is_active = true 
                    ORDER BY role_name";
            
            $stmt = $this->db->getPDO()->prepare($sql);
            $stmt->bindParam(':org_code', $organizationCode, \PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            error_log("Erreur récupération rôles pour organisation {$organizationCode}: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupère tous les mappings actifs
     * 
     * @return array Liste complète des mappings actifs
     */
    public function getAllActiveMappings(): array
    {
        try {
            $sql = "SELECT * FROM {$this->tableName} 
                    WHERE is_active = true 
                    ORDER BY organization_code, role_name";
            
            $stmt = $this->db->getPDO()->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            error_log("Erreur récupération de tous les mappings: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupère un mapping spécifique par organisation et rôle
     * 
     * @param string $organizationCode Code organisation
     * @param string $roleName Nom du rôle
     * @return array|null Mapping trouvé ou null
     */
    public function findByOrganizationAndRole(string $organizationCode, string $roleName): ?array
    {
        try {
            $sql = "SELECT * FROM {$this->tableName} 
                    WHERE organization_code = :org_code 
                    AND role_name = :role_name 
                    LIMIT 1";
            
            $stmt = $this->db->getPDO()->prepare($sql);
            $stmt->bindParam(':org_code', $organizationCode, \PDO::PARAM_STR);
            $stmt->bindParam(':role_name', $roleName, \PDO::PARAM_STR);
            $stmt->execute();
            
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result ?: null;
            
        } catch (\Exception $e) {
            error_log("Erreur recherche mapping {$organizationCode}/{$roleName}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Vérifie si un mapping existe et est actif
     * 
     * @param string $organizationCode Code organisation
     * @param string $roleName Nom du rôle
     * @return bool True si le mapping existe et est actif
     */
    public function mappingExists(string $organizationCode, string $roleName): bool
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->tableName} 
                    WHERE organization_code = :org_code 
                    AND role_name = :role_name 
                    AND is_active = true";
            
            $stmt = $this->db->getPDO()->prepare($sql);
            $stmt->bindParam(':org_code', $organizationCode, \PDO::PARAM_STR);
            $stmt->bindParam(':role_name', $roleName, \PDO::PARAM_STR);
            $stmt->execute();
            
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result['count'] > 0;
            
        } catch (\Exception $e) {
            error_log("Erreur vérification existence mapping {$organizationCode}/{$roleName}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crée un nouveau mapping
     * 
     * @param array $data Données du mapping
     * @return array|false Mapping créé ou false en cas d'erreur
     */
    public function createMapping(array $data)
    {
        try {
            // Vérification de l'unicité
            if ($this->mappingExists($data['organization_code'], $data['role_name'])) {
                throw new \Exception("Un mapping existe déjà pour cette organisation et ce rôle");
            }
            
            $sql = "INSERT INTO {$this->tableName} 
                    (organization_code, role_name, role_dc, role_ou, description, created_by) 
                    VALUES (:org_code, :role_name, :role_dc, :role_ou, :description, :created_by)";
            
            $stmt = $this->db->getPDO()->prepare($sql);
            $stmt->bindParam(':org_code', $data['organization_code'], \PDO::PARAM_STR);
            $stmt->bindParam(':role_name', $data['role_name'], \PDO::PARAM_STR);
            $stmt->bindParam(':role_dc', $data['role_dc'] ?? 'gardiansesame', \PDO::PARAM_STR);
            $stmt->bindParam(':role_ou', $data['role_ou'] ?? 'Groups', \PDO::PARAM_STR);
            $stmt->bindParam(':description', $data['description'] ?? '', \PDO::PARAM_STR);
            $stmt->bindParam(':created_by', $data['created_by'] ?? 'SYSTEM', \PDO::PARAM_STR);
            
            $stmt->execute();
            
            // Récupérer l'enregistrement créé
            $id = $this->db->getPDO()->lastInsertId();
            return $this->findById($id);
            
        } catch (\Exception $e) {
            error_log("Erreur création mapping: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère un mapping par ID
     * 
     * @param int $id ID du mapping
     * @return array|null
     */
    private function findById(int $id): ?array
    {
        try {
            $sql = "SELECT * FROM {$this->tableName} WHERE id = :id LIMIT 1";
            $stmt = $this->db->getPDO()->prepare($sql);
            $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result ?: null;
            
        } catch (\Exception $e) {
            error_log("Erreur récupération mapping par ID {$id}: " . $e->getMessage());
            return null;
        }
    }
}
