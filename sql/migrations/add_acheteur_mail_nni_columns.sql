-- Migration pour ajouter les champs acheteur_mail et acheteur_nni à la table sheet_headers
-- Date: 2026-09-15

ALTER TABLE "EHA".sheet_headers
ADD COLUMN acheteur_mail TEXT DEFAULT NULL,
ADD COLUMN acheteur_nni TEXT DEFAULT NULL;

COMMENT ON COLUMN "EHA".sheet_headers.acheteur_mail IS 'Email de l''acheteur responsable (fourni par HLK) - utilisé pour la notification de création automatique de fiche';
COMMENT ON COLUMN "EHA".sheet_headers.acheteur_nni IS 'NNI de l''acheteur responsable (fourni par HLK) - clé de regroupement pour la notification de création automatique de fiche';
