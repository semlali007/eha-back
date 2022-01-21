ALTER TABLE "EHA".user_news ALTER COLUMN page TYPE VARCHAR(50);
ALTER TABLE "EHA".news ALTER COLUMN title TYPE TEXT;
ALTER TABLE "EHA".news  ADD COLUMN type VARCHAR (50) ;
INSERT INTO "EHA".static_page(page, content, active, title)
VALUES ('home_page', 'Page principale', false, 'Page principale');


INSERT INTO "EHA".hotline_email VALUES (104, 'TP', 'Déplacement', 'da-dag-achatpedia@edf.fr', true);
INSERT INTO "EHA".hotline_email VALUES (205, 'PR', 'pour démonstration', 'da-dag-achatpedia@edf.fr', true);
INSERT INTO "EHA".hotline_email VALUES (206, 'IN', 'pour démonstration', 'da-dag-achatpedia@edf.fr', true);
INSERT INTO "EHA".hotline_email VALUES (105, 'TP', 'Facility management', 'da-dag-achatpedia@edf.fr', true);
INSERT INTO "EHA".hotline_email VALUES (106, 'TP', 'Formation-RetD', 'da-dag-achatpedia@edf.fr', true);
INSERT INTO "EHA".hotline_email VALUES (107, 'TP', 'Fournitures industrielles', 'da-dag-achatpedia@edf.fr', true);
INSERT INTO "EHA".hotline_email VALUES (108, 'TP', 'Génie civil', 'da-dag-achatpedia@edf.fr', true);
INSERT INTO "EHA".hotline_email VALUES (109, 'TP', 'Mobilier de bureau', 'da-dag-achatpedia@edf.fr', true);
INSERT INTO "EHA".hotline_email VALUES (110, 'TP', 'Prestations administratives', 'da-dag-achatpedia@edf.fr', true);
INSERT INTO "EHA".hotline_email VALUES (111, 'TP', 'Prestations industrielles', 'da-dag-achatpedia@edf.fr', true);
INSERT INTO "EHA".hotline_email VALUES (112, 'TP', 'Processus Achat-SI', 'da-dag-achatpedia@edf.fr', true);
INSERT INTO "EHA".hotline_email VALUES (114, 'TP', 'Transport-logistique', 'da-dag-achatpedia@edf.fr', true);
INSERT INTO "EHA".hotline_email VALUES (115, 'TP', 'Travaux immobiliers', 'da-dag-achatpedia@edf.fr', true);
INSERT INTO "EHA".hotline_email VALUES (116, 'TP', 'Véhicules', 'da-dag-achatpedia@edf.fr', true);
INSERT INTO "EHA".hotline_email VALUES (201, 'IT', 'Logiciels', 'da-dag-achatpedia@edf.fr', true);
INSERT INTO "EHA".hotline_email VALUES (202, 'IT', 'Matériels', 'da-dag-achatpedia@edf.fr', true);
INSERT INTO "EHA".hotline_email VALUES (203, 'IT', 'Prestations', 'da-dag-achatpedia@edf.fr', true);
INSERT INTO "EHA".hotline_email VALUES (204, 'IT', 'Télécommunications', 'da-dag-achatpedia@edf.fr', true);
INSERT INTO "EHA".hotline_email VALUES (101, 'TP', 'Bâtiment provisoire', 'da-dag-achatpedia@edf.fr', false);
INSERT INTO "EHA".hotline_email VALUES (102, 'TP', 'Communication-publicité', 'da-dag-achatpedia@edf.fr', false);
INSERT INTO "EHA".hotline_email VALUES (103, 'TP', 'Conseil', 'da-dag-achatpedia@edf.fr', true);
INSERT INTO "EHA".hotline_email VALUES (113, 'TP', 'Restauration', 'da-dag-achatpedia@edf.fr', true);


INSERT INTO "EHA".hotline_groupe VALUES ('TP', 'Tertiaire et Prestations', true);
INSERT INTO "EHA".hotline_groupe VALUES ('IT', ' Informatique et Télécommunications ', true);
INSERT INTO "EHA".hotline_groupe VALUES ('PR', 'Production', true);
INSERT INTO "EHA".hotline_groupe VALUES ('IN', 'Ingénierie', true);

INSERT INTO "EHA".hotline_theme VALUES (11, 'Question sur demande d''achat', true);
INSERT INTO "EHA".hotline_theme VALUES (12, 'Question sur Marchés en cours', true);
INSERT INTO "EHA".hotline_theme VALUES (13, 'Problème de Modélisation contrat (Fournisseur bloqué, SIRET inactif....)', true);
INSERT INTO "EHA".hotline_theme VALUES (14, 'Commande déléguée (Mode Contrôle)', true);
INSERT INTO "EHA".hotline_theme VALUES (15, 'Relations Fournisseurs', true);
INSERT INTO "EHA".hotline_theme VALUES (16, 'Liens article fournisseur', true);
INSERT INTO "EHA".hotline_theme VALUES (17, 'Création de fournisseur', true);
INSERT INTO "EHA".hotline_theme VALUES (18, 'Question matériel informatique et consommables bureautiques', true);
INSERT INTO "EHA".hotline_theme VALUES (19, 'Achat de licences logiciels et maintenance associée', true);
INSERT INTO "EHA".hotline_theme VALUES (20, 'Réclamation', true);
INSERT INTO "EHA".hotline_theme VALUES (21, 'Divers', true);
INSERT INTO "EHA".hotline_theme VALUES (22, 'Fiches Marchés', true);