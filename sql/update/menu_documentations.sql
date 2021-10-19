CREATE TABLE {{ database.schemaName }}.menu_documentations (
    id SERIAL PRIMARY KEY,
    label text NOT NULL,
    position integer NOT NULL,
    parent integer NOT NULL,
    link text,
    external boolean NOT NULL DEFAULT true, 
    breadcrumb text NOT NULL,
	warning text NULL,
	description text NULL
);