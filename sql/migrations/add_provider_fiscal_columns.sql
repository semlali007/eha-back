-- Migration pour enrichir la table provider avec les identifiants fiscaux
-- Date: 2026-06-16
-- Description: Ajout des colonnes SIREN, numéro TVA intracommunautaire et code
--              NAF/APE à la table provider, en préparation de l'enrichissement
--              depuis le référentiel CSV (INSEE/Sirene-like).
--
--              On reste au niveau "entreprise" (SIREN) et non "établissement"
--              (SIRET) : EHA signe des contrats avec une entreprise, peu importe
--              le nombre de ses agences. Le SIRET n'est PAS ajouté.
--
--              Toutes les colonnes sont nullable : pas de casse sur les 1011
--              lignes existantes qui n'auront pas (encore) ces données.
--
-- Idempotente (IF NOT EXISTS partout).

BEGIN;

-- 1. Ajout des 3 colonnes fiscales
ALTER TABLE "EHA".provider
    ADD COLUMN IF NOT EXISTS provider_siren      VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS provider_numero_tva VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS provider_code_naf   VARCHAR(50)  DEFAULT NULL;

-- 2. Documentation
COMMENT ON COLUMN "EHA".provider.provider_siren      IS 'Numéro SIREN (9 chiffres) - identifiant entreprise INSEE';
COMMENT ON COLUMN "EHA".provider.provider_numero_tva IS 'Numéro de TVA intracommunautaire (ex: FR12345678901)';
COMMENT ON COLUMN "EHA".provider.provider_code_naf   IS 'Code NAF/APE de l''activité principale (ex: 41.10D)';

-- 3. Index partiel sur SIREN — futur point de matching et clé business
--    Pas UNIQUE pour le moment : on autorise les NULL et les doublons éventuels
--    (à passer en UNIQUE après nettoyage si besoin).
CREATE INDEX IF NOT EXISTS idx_provider_siren ON "EHA".provider (provider_siren)
    WHERE provider_siren IS NOT NULL;

COMMIT;

-- Vérification :
--   SELECT column_name, data_type, character_maximum_length, is_nullable
--   FROM information_schema.columns
--   WHERE table_schema='EHA' AND table_name='provider'
--   ORDER BY ordinal_position;
