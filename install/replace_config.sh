#!/bin/sh
set +x
###################################################################
# NAME :  replace_config.sh
# FONCTIONALITY :   preinstalltion , mise a jour des variable d'apres fichier de configuration
# PARENT SCRIPT :
# SCOPE :
# KEYWORDS :
# CREATION DATE : 03 Avril  2020
# VERSION : V0
# AUTHOR : pascal salviat
# COMMENTS :
# ------------------------------------------------------------------------------
# MODIFICATION DATE :
# VERSION :
# AUTHOR :
# OBJECT :
# ------------------------------------------------------------------------------
###################################################################
#variables
VERSION=V0
# V A R I A B L E S     F I X E S
# -------------------------------------------------------------
HOMEP=$(dirname $0)
DIRINS=$(pwd)
RET=0
#
# Source environment variables
#

if [ -f ${HOMEP}/config.txt ]; then
    . ${HOMEP}/config.txt
else
    echo "Source variable non disponible config.txt dans $HOMEP"
    RET=98
    exit 99
fi
# replace dans eha-source

for rep in htdocs scripts sql
do
cd $DIRINS
cd ../$rep
find  -type f -exec sed -i -e "s/{{ database.bddUser }}/$config_bdd_username/g" {} \;
find  -type f -exec sed -i -e "s/{{ vault_database.bddPassword }}/$config_bdd_password/g" {} \;
find  -type f -exec sed -i -e "s/{{ database.databaseName }}/$config_bdd_database/g" {} \;
find  -type f -exec sed -i -e "s/{{ database.schemaName }}/$config_bdd_schema/g" {} \;
find  -type f -exec sed -i -e "s/{{ vault_database.bddPassword }}/$config_bdd_password/g" {} \;
find  -type f -exec sed -i -e "s/{{ database.listenPort }}/$config_bdd_port/g" {} \;
find  -type f -exec sed -i -e "s/{{ SITE_PADAONE_UX }}/$config_padaone/g" {} \;
find  -type f -exec sed -i -e "s|{{ gardian_host }}|$config_gardian_host|g" {} \;
find  -type f -exec sed -i -e "s/{{ gardian_ldap_rdn }}/$config_gardian_ldap_rdn/g" {} \;
find  -type f -exec sed -i -e "s/{{ vault_gardian }}/$config_gardian_ldap_pwd/g" {} \;
find  -type f -exec sed -i -e "s/{{ site_domaine }}/$config_domaine/g" {} \;
find  -type f -exec sed -i -e "s/{{ MAILTO_CONTACT }}/$config_contact_mail/g" {} \;
find  -type f -exec sed -i -e "s/{{ vault_asif }}/$config_password_asif/g" {} \;
find  -type f -exec sed -i -e "s/{{ INSTANCE_WEB }}/$config_instance_web/g" {} \;



done






