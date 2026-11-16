-- Ajoute la colonne auto_created à sheet_headers.
-- true = la fiche a été créée par un batch automatique (auto-import HLK,
--        future migration, import Cockpit direct, etc.).
-- false = création manuelle via l'UI (comportement historique).
--
-- Défaut false et NOT NULL : évite toute ambiguïté sur les fiches existantes.
-- Aucune migration de données nécessaire (les fiches existantes créées à la
-- main resteront à false, ce qui est correct).

ALTER TABLE "EHA".sheet_headers
    ADD COLUMN IF NOT EXISTS auto_created BOOLEAN NOT NULL DEFAULT false;

COMMENT ON COLUMN "EHA".sheet_headers.auto_created IS
    'true si la fiche a été créée par un batch automatique (auto-import HLK, etc.). false pour une création manuelle via l''UI.';
