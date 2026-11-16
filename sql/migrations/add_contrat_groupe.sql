-- Migration pour ajouter le champ contrat_groupe à la table sheet_headers
-- Date: 2025-06-10

ALTER TABLE "EHA".sheet_headers 
ADD COLUMN contrat_groupe VARCHAR(10) DEFAULT 'false';

-- Index pour améliorer les performances des requêtes
CREATE INDEX idx_sheet_headers_contrat_groupe ON "EHA".sheet_headers (contrat_groupe);

-- Commentaire pour documenter le champ
COMMENT ON COLUMN "EHA".sheet_headers.contrat_groupe IS 'Indique si la fiche concerne un contrat groupe (true/false)';