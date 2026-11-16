-- Migration pour ajouter le champ mise_a_jour_donnee à la table sheet_headers
-- Ce champ représente l'état de la mise à jour de la donnée : 'oui', 'en_cours', 'non'

-- Ajouter la colonne mise_a_jour_donnee
ALTER TABLE "EHA".sheet_headers 
ADD COLUMN mise_a_jour_donnee VARCHAR(10) DEFAULT 'non' CHECK (mise_a_jour_donnee IN ('oui', 'en_cours', 'non'));

-- Créer un index pour améliorer les performances des filtres
CREATE INDEX idx_sheet_headers_mise_a_jour_donnee ON "EHA".sheet_headers (mise_a_jour_donnee);

-- Ajouter un commentaire pour documenter le champ
COMMENT ON COLUMN "EHA".sheet_headers.mise_a_jour_donnee IS 'État de la mise à jour de la donnée : oui, en_cours, non';

-- Mettre à jour les enregistrements existants avec la valeur par défaut
UPDATE "EHA".sheet_headers SET mise_a_jour_donnee = 'non' WHERE mise_a_jour_donnee IS NULL;
