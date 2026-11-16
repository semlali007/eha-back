-- Ajoute la colonne achat_id_cockpit à sheet_headers.
-- Cette colonne sert de clé d'idempotence pour les batchs d'import qui poussent
-- des fiches depuis Cockpit / HLK. Elle remplace l'usage de netachat_id qui
-- était historique et flou sémantiquement.
--
-- Nullable : les fiches créées manuellement via l'UI n'en auront pas.
-- Indexée (partielle) pour les lookups rapides côté batch.

ALTER TABLE "EHA".sheet_headers
    ADD COLUMN IF NOT EXISTS achat_id_cockpit BIGINT NULL;

CREATE INDEX IF NOT EXISTS idx_sheet_headers_achat_id_cockpit
    ON "EHA".sheet_headers (achat_id_cockpit)
    WHERE achat_id_cockpit IS NOT NULL;

COMMENT ON COLUMN "EHA".sheet_headers.achat_id_cockpit IS
    'Identifiant Cockpit de l''achat (achat_id_cockpit HLK). Utilisé pour l''idempotence des imports auto.';
