/*
	Utiliser la base de données  {{ database..databaseName }}
	avec l'utilisateur  {{ database.bddUser }}
*/
CREATE SCHEMA {{ database.schemaName }} AUTHORIZATION {{ database.bddUser }};


CREATE TABLE {{ database.schemaName }}.administrators (
	email VARCHAR(100) PRIMARY KEY,
	living boolean not null
);

CREATE TABLE {{ database.schemaName }}.externes (
	externe_email VARCHAR(100) PRIMARY KEY,
	demandeur_email VARCHAR(100) NOT NULL,
	date_fin DATE NOT NULL
);



CREATE TABLE {{ database.schemaName }}.repository (
	family VARCHAR(9) NOT NULL,
	code VARCHAR(20) NOT NULL,
	label VARCHAR(50) NOT NULL
);



CREATE TABLE {{ database.schemaName }}.stats (
	table_name VARCHAR(19) NOT NULL,
	updated_date TIMESTAMP NULL
);
ALTER TABLE {{ database.schemaName }}.stats ADD PRIMARY KEY (table_name);



CREATE TABLE {{ database.schemaName }}.contract (
	contract_system VARCHAR(2) NOT NULL,
	contract_system_label VARCHAR(50) NOT NULL,
	contract_code VARCHAR(20) NOT NULL,
	contract_label VARCHAR(250) NOT NULL,
	contract_date_start DATE NOT NULL,
	contract_date_end DATE NOT NULL,
	provider_code VARCHAR(10) NULL,
	provider_label VARCHAR(50) NULL,
	entity_code VARCHAR(10) NULL,
	entity_label VARCHAR(50) NULL,
	organisation_achat_code VARCHAR(10) NULL,
	organisation_achat_label VARCHAR(50) NULL,
	contract_post_code VARCHAR(5) NOT NULL,
	contract_post_label VARCHAR(90) NULL,
	item_code VARCHAR(20) NOT NULL,
	item_label VARCHAR(90) NOT NULL,
	agency_code VARCHAR(10) NULL,
	agency_label VARCHAR(50) NULL,
	jauge SMALLINT NOT NULL,
	/* contract_code + contract_label */
	search2 VARCHAR(300) NOT NULL,
	/* contract_code + contract_label + contract_post_label */
	search1 VARCHAR(400) NOT NULL,
	/* contract_code + contract_label + contract_post_label + provider_code + provider_label */
	search0 VARCHAR(600) NOT NULL
);
ALTER TABLE {{ database.schemaName }}.contract ADD PRIMARY KEY (contract_system, contract_code , provider_code, contract_post_code, item_code );
CREATE INDEX idx_contract_provider ON {{ database.schemaName }}.contract(provider_code);



CREATE TABLE {{ database.schemaName }}.item_generic (
	item_code VARCHAR(20) NOT NULL,
	item_label VARCHAR(90) NOT NULL,
	item_label_long VARCHAR(180) NOT NULL,
	item_notice VARCHAR(1024) NULL,
	item_level_1 VARCHAR(50) NOT NULL,
	item_level_2 VARCHAR(50) NOT NULL,
	item_level_3 VARCHAR(50) NOT NULL,
	comptes_e VARCHAR(30) NULL,
	comptes_i VARCHAR(30) NULL,
	tva VARCHAR(30) NOT NULL,
	/* item_code + item_label */
	search2 VARCHAR(110) NOT NULL,
	/* item_code + item_label_long */
	search1 VARCHAR(200) NOT NULL,
	/* item_code + level_1 + level_2 + level_3 + item_label_long + notice */
	search0 VARCHAR(1500) NOT NULL
);
ALTER TABLE {{ database.schemaName }}.item_generic ADD PRIMARY KEY ( item_code );



CREATE TABLE {{ database.schemaName }}.provider (
	provider_code VARCHAR(10) NOT NULL,
	provider_label VARCHAR(50) NOT NULL,
	provider_country VARCHAR(9) NOT NULL,
	provider_zipcode VARCHAR(20) NOT NULL,
	provider_town VARCHAR(50) NOT NULL,
	provider_siret VARCHAR(20) NOT NULL,
	provider_nace VARCHAR(20) NOT NULL,
	/* provider_code + provider_label */
	search1 VARCHAR(60) NOT NULL,
	/* provider_code + provider_label + provider_town + provider_siret*/
	search0 VARCHAR(130) NOT NULL,
	x INT NULL,
	y INT NULL,
	activities VARCHAR(255) NULL,
	weight INT NOT NULL DEFAULT 0/* nombre de lignes affectées au fournisseur dans la table des contrats */
);
ALTER TABLE {{ database.schemaName }}.provider ADD PRIMARY KEY ( provider_code );
CREATE INDEX idx_provider_x_y ON {{ database.schemaName }}.provider(x, y);



CREATE TABLE {{ database.schemaName }}.provider_item (
	row SERIAL PRIMARY KEY,
	provider_code VARCHAR(10) NOT NULL,
	item_code VARCHAR(20) NOT NULL
);
CREATE UNIQUE INDEX idx_provider_item ON {{ database.schemaName }}.provider_item(provider_code, item_code);



CREATE TABLE {{ database.schemaName }}.dauphin (
	code INT NOT NULL,                 -- item_id
	url VARCHAR(200) NOT NULL,         -- item_url
	label VARCHAR(800) NOT NULL,       -- pdt_summary + label
	contract VARCHAR(200) NOT NULL,     -- contrat_ref
	contract_begin_date DATE NOT NULL, -- item_ctr_begin_date
	contract_end_date DATE NOT NULL,   -- item_ctr_end_date
	/* dauphin_label */
	search VARCHAR(800) NOT NULL
);
ALTER TABLE {{ database.schemaName }}.dauphin ADD PRIMARY KEY ( code );



CREATE TABLE {{ database.schemaName }}.hotline (
	discussion SERIAL PRIMARY KEY,
	opened_at TIMESTAMP NOT NULL,
	email_from VARCHAR(100) NOT NULL,
	info_entity VARCHAR(50) NOT NULL,
	category_id SMALLINT NOT NULL,
	question VARCHAR(500) NOT NULL,
	sha_demandeur VARCHAR(50) NOT NULL,
	sha_hotline VARCHAR(50) NOT NULL,
	closed_at TIMESTAMP NULL,
	closed_note SMALLINT NULL,
	theme_id SMALLINT NULL
);
CREATE INDEX idx_hotline_email_from ON {{ database.schemaName }}.hotline(email_from);

CREATE TABLE {{ database.schemaName }}.hotline_theme (
	id SMALLINT PRIMARY KEY,
	label VARCHAR(100) NOT NULL,
	living boolean NOT NULL
);

CREATE TABLE {{ database.schemaName }}.hotline_groupe (
	id VARCHAR(5) PRIMARY KEY,
	label VARCHAR(100) NOT NULL,
	living boolean NOT NULL
);

CREATE TABLE {{ database.schemaName }}.hotline_email (
	id SMALLINT PRIMARY KEY,
	groupe VARCHAR(5) NOT NULL,
	category VARCHAR(100) NOT NULL,
	email VARCHAR(100) NOT NULL,
	living boolean NOT NULL
);

CREATE TABLE {{ database.schemaName }}.hotline_fil (
	row SERIAL PRIMARY KEY,
	discussion INT NOT NULL,
	/*
		O = open
		F = forward
		P = post
		U = upload of document
		E = feedback of the user experience before closing
		C = close
	*/
	nature CHAR(1) NOT NULL,
	quand TIMESTAMP NOT NULL,
	is_hotline BOOLEAN NOT NULL,
	email VARCHAR(100) NOT NULL,
	message VARCHAR(5000) NOT NULL
);
CREATE INDEX idx_hotline_fil_discussion ON {{ database.schemaName }}.hotline_fil(discussion);

CREATE TABLE {{ database.schemaName }}.hotline_fichiers (
	row SERIAL PRIMARY KEY,
	discussion INT NOT NULL,
	file_code VARCHAR(100) NOT NULL,
	file_name VARCHAR(250) NOT NULL
);
CREATE UNIQUE INDEX idx_hotline_fichiers ON {{ database.schemaName }}.hotline_fichiers(file_code);

/* Géo localisation des villes */
CREATE TABLE {{ database.schemaName }}.french_cities (
	id_city SERIAL,
	zipcode VARCHAR(20) NOT NULL,
	latitude FLOAT NOT NULL,
	longitude FLOAT NOT NULL,
	x INT NOT NULL,
	y INT NOT NULL
);
CREATE UNIQUE INDEX idx_cities_zipcode ON {{ database.schemaName }}.french_cities(zipcode);



CREATE TABLE {{ database.schemaName }}.tenders (
	row SERIAL PRIMARY KEY,
	tender_code CHAR(13) NOT NULL,
	description_short VARCHAR(180) NOT NULL,
	description_long VARCHAR(9000) NOT NULL,
	provider_label VARCHAR(200) NULL,
	agency_code VARCHAR(30) NOT NULL,
	buyer_code VARCHAR(9) NOT NULL,
	buyer_label VARCHAR(50) NOT NULL,
	max_date DATE NOT NULL,
	search1 VARCHAR(200) NOT NULL,
	search0 VARCHAR(9000) NOT NULL
);
