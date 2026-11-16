<?php
namespace App\Models;

/**
 * Modèle pour la gestion des mappings organisation → rôle
 * 
 * Cette classe gère l'attribution automatique de rôles
 * basée sur l'organisation de l'utilisateur (gardianwebsso_org)
 * 
 * @author EHA Team
 * @since 2025-08-21
 */
class OrgRoleMapping extends Model 
{
    /**
     * Nom de la table en base de données
     */
    protected $table = 'org_role_mapping';
    
    /**
     * Clé primaire de la table
     */
    protected $primaryKey = 'id';
    
    /**
     * Champs autorisés pour l'assignation en masse
     */
    protected $fillable = [
        'organization_code',
        'role_name', 
        'role_dc',
        'role_ou',
        'is_active',
        'description',
        'created_by'
    ];
    
    /**
     * Champs avec des valeurs par défaut
     */
    protected $attributes = [
        'role_dc' => 'gardiansesame',
        'role_ou' => 'Groups',
        'is_active' => true
    ];
    
    /**
     * Valide les données avant sauvegarde
     * 
     * @param array $data Données à valider
     * @return array Erreurs de validation
     */
    public function validate($data)
    {
        $errors = [];
        
        if (empty($data['organization_code'])) {
            $errors[] = 'Le code organisation est requis';
        }
        
        if (empty($data['role_name'])) {
            $errors[] = 'Le nom du rôle est requis';
        }
        
        // Validation du format du code organisation (numérique)
        if (!empty($data['organization_code']) && !preg_match('/^\d+$/', $data['organization_code'])) {
            $errors[] = 'Le code organisation doit être numérique';
        }
        
        // Validation du nom de rôle (format EHA_*)
        if (!empty($data['role_name']) && !preg_match('/^EHA_[A-Z_]+$/', $data['role_name'])) {
            $errors[] = 'Le nom du rôle doit suivre le format EHA_XXXXX';
        }
        
        return $errors;
    }
    
    /**
     * Scope pour récupérer uniquement les mappings actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope pour filtrer par code organisation
     */
    public function scopeByOrganization($query, $organizationCode)
    {
        return $query->where('organization_code', $organizationCode);
    }
}
