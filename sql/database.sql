/*

We have to have:
	SHOW standard_conforming_strings;     =>    on

List databases => \l
Liste tables   => \dt

*/


DROP DATABASE {{ database.databaseName }};

DROP USER {{ database.bddUser }};

CREATE USER {{ database.bddUser }} WITH PASSWORD '{{ vault_database.bddPassword }}';

CREATE DATABASE {{ database.databaseName }} WITH OWNER {{ database.bddUser }} ENCODING 'UTF8' ;

DROP SCHEMA public;

