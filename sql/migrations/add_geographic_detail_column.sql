-- Migration pour ajouter le champ geographic_detail à la table sheet_headers
-- Date: 2025-07-15

-- Ajouter la colonne geographic_detail
ALTER TABLE "EHA".sheet_headers 
ADD COLUMN geographic_detail TEXT DEFAULT NULL;

-- Ajouter un commentaire pour documenter la colonne
COMMENT ON COLUMN "EHA".sheet_headers.geographic_detail IS 'Zone géographique détail si région - informations complémentaires sur la zone géographique';

-- Créer un index pour optimiser les recherches si nécessaire
-- CREATE INDEX IF NOT EXISTS idx_sheet_headers_geographic_detail ON "EHA".sheet_headers USING gin(to_tsvector('french', coalesce(geographic_detail, '')));
