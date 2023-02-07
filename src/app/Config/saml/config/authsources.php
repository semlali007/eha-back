<?php
require_once(__DIR__ . '/../../../../app/Config/saml/include.php');

$config = [
    'admin' => [
        'core:AdminPassword',
    ],
    'default-sp' => [
        'saml:SP',
        'entityID' => getenv('SAML_ENTITY_ID'),
        'idp' => getenv('SAML_IDP'),
        'discoURL' => null,
    ],
];
