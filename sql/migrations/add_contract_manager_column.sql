-- Migration : Ajout du champ "Contract manager / Acheteur"
-- Date : 08/07/2025

ALTER TABLE sheet_headers 
ADD COLUMN contract_manager VARCHAR(255) DEFAULT NULL;

-- Commentaire pour la documentation
COMMENT ON COLUMN sheet_headers.contract_manager IS 'Nom du contract manager ou acheteur responsable du contrat';
