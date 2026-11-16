-- Migration: Création de la table de mapping organisation → rôles
-- Auteur: EHA Team
-- Date: 2025-08-21

-- Table principale de mapping organisation → rôle
CREATE TABLE IF NOT EXISTS org_role_mapping (
    id SERIAL PRIMARY KEY,
    organization_code VARCHAR(50) NOT NULL,
    role_name VARCHAR(100) NOT NULL,
    role_dc VARCHAR(100) DEFAULT 'gardiansesame',
    role_ou VARCHAR(100) DEFAULT 'Groups',
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by VARCHAR(100),
    description TEXT,
    
    -- Contrainte d'unicité pour éviter les doublons
    UNIQUE(organization_code, role_name)
);

-- Index pour optimiser les performances
CREATE INDEX IF NOT EXISTS idx_org_role_mapping_org_code ON org_role_mapping(organization_code);
CREATE INDEX IF NOT EXISTS idx_org_role_mapping_active ON org_role_mapping(is_active);
CREATE INDEX IF NOT EXISTS idx_org_role_mapping_role_name ON org_role_mapping(role_name);

-- Trigger pour mise à jour automatique du champ updated_at
CREATE OR REPLACE FUNCTION update_org_role_mapping_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_org_role_mapping_updated_at
    BEFORE UPDATE ON org_role_mapping
    FOR EACH ROW
    EXECUTE FUNCTION update_org_role_mapping_updated_at();

-- Données initiales pour le besoin spécifique
INSERT INTO org_role_mapping (organization_code, role_name, description, created_by) 
VALUES 
    ('62172702', 'EHA_GESTIONNAIRE', 'Attribution automatique du rôle gestionnaire pour organisation 62172702', 'SYSTEM')
ON CONFLICT (organization_code, role_name) DO NOTHING;

-- Commentaires sur la table et les colonnes
COMMENT ON TABLE org_role_mapping IS 'Table de mapping pour attribution automatique de rôles basée sur l''organisation utilisateur';
COMMENT ON COLUMN org_role_mapping.organization_code IS 'Code organisation (ex: valeur de gardianwebsso_org)';
COMMENT ON COLUMN org_role_mapping.role_name IS 'Nom du rôle à attribuer (ex: EHA_GESTIONNAIRE)';
COMMENT ON COLUMN org_role_mapping.role_dc IS 'Domaine du rôle (par défaut: gardiansesame)';
COMMENT ON COLUMN org_role_mapping.role_ou IS 'Unité organisationnelle du rôle (par défaut: Groups)';
COMMENT ON COLUMN org_role_mapping.is_active IS 'Indique si le mapping est actif';
COMMENT ON COLUMN org_role_mapping.description IS 'Description métier du mapping';
