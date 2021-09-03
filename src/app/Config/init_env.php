<?php

if(is_null($_ENV["DB_HOST"]) || $_ENV["DB_HOST"] == 'null'){
    putenv("DB_HOST=$config_bdd_servername") ;
}

if(is_null($_ENV["DB_NAME"]) || $_ENV["DB_NAME"] == 'null'){
    putenv("DB_NAME=$config_bdd_database") ;
}

if(is_null($_ENV["DB_USER"]) || $_ENV["DB_USER"] == 'null' ){
    putenv("DB_USER=$config_bdd_username") ;
}

if(is_null($_ENV["DB_PWD"]) || $_ENV["DB_PWD"] == 'null'){
    putenv("DB_PWD=$config_bdd_password") ;
}

if(is_null($_ENV["DB_SCHEMA"]) || $_ENV["DB_SCHEMA"] == 'null'){
    putenv("DB_SCHEMA=$config_bdd_schema") ;
}

if(is_null($_ENV["DB_PORT"]) || $_ENV["DB_PORT"] == 'null'){
    putenv("DB_PORT=$config_bdd_port") ;
}

if(is_null($_ENV["CONFIG_GARDIAN_HOST"]) || $_ENV["CONFIG_GARDIAN_HOST"] == 'null'){
    putenv("CONFIG_GARDIAN_HOST=$config_gardian_host") ;
}

if(is_null($_ENV["CONFIG_GARDIAN_LDAP_RDN"]) || $_ENV["CONFIG_GARDIAN_LDAP_RDN"] == 'null'){
    putenv("CONFIG_GARDIAN_LDAP_RDN=$config_gardian_ldap_rdn") ;
}

if(is_null($_ENV["CONFIG_GARDIAN_LDAP_PWD"]) || $_ENV["CONFIG_GARDIAN_LDAP_PWD"] == 'null'){
    putenv("CONFIG_GARDIAN_LDAP_PWD=$config_gardian_ldap_pwd") ;
}

if(is_null($_ENV["CONFIG_NOREPLY_EMAIL"]) || $_ENV["CONFIG_NOREPLY_EMAIL"] == 'null'){
    putenv("CONFIG_NOREPLY_EMAIL=$config_noreply_email") ;
}

if(is_null($_ENV["CONFIG_DOMAIN"]) || $_ENV["CONFIG_DOMAIN"] == 'null'){
    putenv("CONFIG_DOMAIN=$config_domaine") ;
}

if(is_null($_ENV["CONFIG_UPLOAD_PATH"]) || $_ENV["CONFIG_UPLOAD_PATH"] == 'null'){
    putenv("CONFIG_UPLOAD_PATH=$config_upload_path") ;
}

if(is_null($_ENV["CONFIG_DOCUMENTATION_FILE"]) || $_ENV["CONFIG_DOCUMENTATION_FILE"] == 'null'){
    putenv("CONFIG_DOCUMENTATION_FILE=$config_documentation_file") ;
}

if(is_null($_ENV["CONFIG_MIGRATION_SOURCE"]) || $_ENV["CONFIG_MIGRATION_SOURCE"] == 'null'){
    putenv("CONFIG_MIGRATION_SOURCE=$config_migration_source") ;
}