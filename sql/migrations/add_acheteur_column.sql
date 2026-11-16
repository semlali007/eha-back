-- Migration pour ajouter le champ acheteur à la table sheet_headers
-- Date: 2025-07-15

-- Ajouter la colonne acheteur
ALTER TABLE "EHA".sheet_headers 
ADD COLUMN acheteur TEXT DEFAULT NULL;

-- Ajouter un commentaire pour documenter la colonne
COMMENT ON COLUMN "EHA".sheet_headers.acheteur IS 'Acheteur - informations sur l''acheteur responsable';

-- Créer un index pour optimiser les recherches si nécessaire
-- CREATE INDEX IF NOT EXISTS idx_sheet_headers_acheteur ON "EHA".sheet_headers USING gin(to_tsvector('french', coalesce(acheteur, '')));
