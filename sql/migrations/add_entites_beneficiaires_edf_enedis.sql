-- Migration pour ajouter le champ "Entités bénéficiaires d'EDF SA / ENEDIS"
-- Fichier: add_entites_beneficiaires_edf_enedis.sql
-- Date: 2025-07-08

ALTER TABLE sheet_headers 
ADD COLUMN entites_beneficiaires_edf_enedis TEXT DEFAULT NULL;

-- Ajouter un commentaire pour documenter le champ
COMMENT ON COLUMN sheet_headers.entites_beneficiaires_edf_enedis IS 'Entités bénéficiaires d''EDF SA / ENEDIS - Texte libre pour préciser les entités spécifiques';
