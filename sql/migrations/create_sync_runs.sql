-- Historique append-only des runs de synchronisation batch.
-- Contrairement à sync_state (1 ligne par job = état courant), sync_runs
-- accumule un enregistrement par exécution — utilisé par l'écran admin
-- pour tracer l'historique, les erreurs, et les fiches produites.
--
-- Réutilisable pour d'autres batchs futurs (sync fournisseurs, référentiels…).

CREATE TABLE IF NOT EXISTS "EHA".sync_runs (
    id                BIGSERIAL PRIMARY KEY,
    job_name          VARCHAR(64) NOT NULL,

    -- Traçabilité temporelle
    started_at        TIMESTAMPTZ NOT NULL,
    finished_at       TIMESTAMPTZ,
    duration_ms       INTEGER,
    status            VARCHAR(16) NOT NULL,   -- 'running' | 'success' | 'partial' | 'error'

    -- Traçabilité du déclenchement
    triggered_by      VARCHAR(64) NOT NULL,   -- 'cron' | 'cli' | 'admin:<NNI>' | 'webhook'
    dry_run           BOOLEAN NOT NULL DEFAULT false,

    -- Curseurs — permet de comprendre "pourquoi ce run n'a rien trouvé"
    signed_since_used TEXT,                   -- valeur envoyée à HLK au début du run
    next_cursor       TEXT,                   -- valeur écrite dans sync_state à la fin

    -- Résultats structurés (JSONB pour flexibilité — chaque batch peut y mettre
    -- ce qu'il veut sans figer le schéma)
    stats             JSONB NOT NULL DEFAULT '{}'::jsonb,
    errors            JSONB,                  -- liste des erreurs row-level
    warnings          JSONB,                  -- liste des warnings (pages HLK skipées, etc.)

    -- Drill-down UI : "voir les N fiches créées par ce run"
    created_sheet_ids INTEGER[]
);

CREATE INDEX IF NOT EXISTS idx_sync_runs_job_started
    ON "EHA".sync_runs (job_name, started_at DESC);
CREATE INDEX IF NOT EXISTS idx_sync_runs_started
    ON "EHA".sync_runs (started_at DESC);

COMMENT ON TABLE  "EHA".sync_runs                 IS 'Historique append-only des exécutions de batchs de sync (1 ligne par run).';
COMMENT ON COLUMN "EHA".sync_runs.triggered_by    IS 'Origine du run : cron, cli, admin:<NNI>, webhook.';
COMMENT ON COLUMN "EHA".sync_runs.dry_run         IS 'true = simulation, aucune écriture en base. Distinct des vrais runs pour filtrer l''historique.';
COMMENT ON COLUMN "EHA".sync_runs.status          IS 'running (en cours) | success (aucune erreur) | partial (erreurs row-level ou pages skipées) | error (fatal).';
COMMENT ON COLUMN "EHA".sync_runs.created_sheet_ids IS 'IDs des fiches (sheet_headers) créées par ce run. Permet le drill-down UI.';
