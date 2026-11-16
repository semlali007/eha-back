-- Migration pour ajouter le champ commentaire_contrat
-- Date: 2025-01-10
-- Description: Ajout du champ commentaire contrat dans la table sheets

ALTER TABLE sheets ADD COLUMN commentaire_contrat TEXT DEFAULT '';

-- Commentaire sur la colonne
COMMENT ON COLUMN sheets.commentaire_contrat IS 'Commentaire relatif au contrat de la fiche';