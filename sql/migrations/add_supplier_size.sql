-- Migration pour ajouter le système de taille fournisseur
-- Date: 2025-06-11
-- Description: Ajout de la table supplier_size et relation avec sheet_headers

-- 1. Création de la table de référence supplier_size
CREATE TABLE IF NOT EXISTS supplier_size (
    id SERIAL PRIMARY KEY,
    size VARCHAR(10) NOT NULL UNIQUE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Fonction trigger pour updated_at (PostgreSQL)
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_supplier_size_updated_at BEFORE UPDATE
    ON supplier_size FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- 3. Insertion des valeurs de référence
INSERT INTO supplier_size (size, is_active) VALUES 
('PME', TRUE),
('ETI', TRUE),
('GE', TRUE)
ON CONFLICT (size) DO UPDATE SET is_active = EXCLUDED.is_active;

-- 4. Ajout de la colonne id_supplier_size dans sheet_headers
ALTER TABLE sheet_headers 
ADD COLUMN IF NOT EXISTS id_supplier_size INTEGER DEFAULT NULL;

-- 5. Ajout de la contrainte de clé étrangère
ALTER TABLE sheet_headers 
ADD CONSTRAINT fk_sheet_headers_supplier_size 
    FOREIGN KEY (id_supplier_size) REFERENCES supplier_size(id);

-- 6. Création d'un index pour optimiser les requêtes
CREATE INDEX IF NOT EXISTS idx_sheet_headers_supplier_size ON sheet_headers(id_supplier_size);

-- 7. Valeurs par défaut (optionnel - permet d'avoir une répartition initiale)
-- UPDATE sheet_headers SET id_supplier_size = 1 WHERE id_supplier_size IS NULL AND id <= 100;
-- UPDATE sheet_headers SET id_supplier_size = 2 WHERE id_supplier_size IS NULL AND id <= 200;
-- UPDATE sheet_headers SET id_supplier_size = 3 WHERE id_supplier_size IS NULL;

COMMIT;
