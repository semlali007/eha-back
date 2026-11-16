-- Migration: Ajout de nouveaux mappings organisation → rôles
-- Auteur: EHA Team
-- Date: 2025-08-28
-- Description: Insertion de codes d'organisation supplémentaires avec attribution du rôle EHA_GESTIONNAIRE

-- Insertion des nouveaux codes d'organisation (en évitant les doublons)
INSERT INTO org_role_mapping (organization_code, role_name, description, created_by) 
VALUES 
    ('62172302', 'EHA_GESTIONNAIRE', 'Attribution automatique du rôle gestionnaire pour organisation 62172302', 'SYSTEM'),
    ('62172002', 'EHA_GESTIONNAIRE', 'Attribution automatique du rôle gestionnaire pour organisation 62172002', 'SYSTEM'),
    ('62172003', 'EHA_GESTIONNAIRE', 'Attribution automatique du rôle gestionnaire pour organisation 62172003', 'SYSTEM'),
    ('62172004', 'EHA_GESTIONNAIRE', 'Attribution automatique du rôle gestionnaire pour organisation 62172004', 'SYSTEM'),
    ('62172005', 'EHA_GESTIONNAIRE', 'Attribution automatique du rôle gestionnaire pour organisation 62172005', 'SYSTEM'),
    ('62172006', 'EHA_GESTIONNAIRE', 'Attribution automatique du rôle gestionnaire pour organisation 62172006', 'SYSTEM'),
    ('62172007', 'EHA_GESTIONNAIRE', 'Attribution automatique du rôle gestionnaire pour organisation 62172007', 'SYSTEM'),
    ('62172008', 'EHA_GESTIONNAIRE', 'Attribution automatique du rôle gestionnaire pour organisation 62172008', 'SYSTEM'),
    ('62172301', 'EHA_GESTIONNAIRE', 'Attribution automatique du rôle gestionnaire pour organisation 62172301', 'SYSTEM'),
    ('62172303', 'EHA_GESTIONNAIRE', 'Attribution automatique du rôle gestionnaire pour organisation 62172303', 'SYSTEM'),
    ('62172304', 'EHA_GESTIONNAIRE', 'Attribution automatique du rôle gestionnaire pour organisation 62172304', 'SYSTEM')
ON CONFLICT (organization_code, role_name) DO NOTHING;

-- Vérification des insertions
SELECT 
    organization_code, 
    role_name, 
    created_at, 
    description 
FROM org_role_mapping 
WHERE organization_code IN (
    '62172302', '62172002', '62172003', '62172004', '62172005', 
    '62172006', '62172007', '62172008', '62172301', '62172303', '62172304'
)
ORDER BY organization_code;
