-- Table utilisée par les batchs de synchronisation pour tracer leur progression.
-- Chaque batch a une ligne identifiée par job_name.
-- Réutilisable pour d'autres syncs futurs (fournisseurs HLK, référentiels, etc.).

CREATE TABLE IF NOT EXISTS "EHA".sync_state (
    job_name        VARCHAR(64)  PRIMARY KEY,
    last_run_at     TIMESTAMPTZ,
    last_success_at TIMESTAMPTZ,
    -- Curseur incrémental (ex: date de signature, timestamp, opaque token…).
    -- Format libre défini par chaque batch.
    last_cursor     TEXT,
    last_status     VARCHAR(16),   -- 'success', 'partial', 'error'
    last_error      TEXT,
    -- Compteurs libres, exposés en JSON pour ne pas figer le schéma.
    -- Ex: {"created": 12, "updated": 3, "skipped": 5, "errors": 0}
    stats           JSONB
);

COMMENT ON TABLE  "EHA".sync_state          IS 'État d''avancement des batchs de synchronisation (curseur, dernière exécution, stats).';
COMMENT ON COLUMN "EHA".sync_state.job_name IS 'Nom du batch, ex: ''import_sheets_from_hlk''.';
