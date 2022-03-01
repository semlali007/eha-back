INSERT INTO "EHA".committee(label) VALUES ('Biens et Services Industriels');
INSERT INTO "EHA".committee(label) VALUES ('Conseil');
INSERT INTO "EHA".committee(label) VALUES ('Véhicules');
INSERT INTO "EHA".committee(label) VALUES ('Immobilier');
INSERT INTO "EHA".committee(label) VALUES ('Travel');
INSERT INTO "EHA".committee(label) VALUES ('Formation');
INSERT INTO "EHA".committee(label) VALUES ('Intérim');
INSERT INTO "EHA".committee(label) VALUES ('Evénementiel');
INSERT INTO "EHA".committee(label) VALUES ('Usages');
INSERT INTO "EHA".committee(label) VALUES ('Logiciels');
INSERT INTO "EHA".committee(label) VALUES ('Prestations');
--
--  Changement de l encodage de la base de données
--
update pg_database set encoding = pg_char_to_encoding('UTF8') where datname = 'EHA';