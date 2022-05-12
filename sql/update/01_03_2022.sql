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

INSERT INTO "EHA".static_page(page, content, active, title)
	VALUES ('welcome-text', '<h1 class="cover-heading">Bienvenue dans la Vitrine de l&rsquo;Offre</h1>
<p><span class="text-muted">Vous recherchez des informations sur un march&eacute; multi-entit&eacute;s, un march&eacute; national ou un march&eacute; Groupe ?<br />Vous les trouverez ici dans le catalogue des march&eacute;s port&eacute;s par la DAG.<br />Vous avez une question ? Contactez-nous <a href="app/hotline/create">ici</a> en s&eacute;lectionnant la rubrique &laquo; Fiches march&eacute; &raquo;.</span></p>', true, "Texte de bienvenue (Vitrine de l'offre)");