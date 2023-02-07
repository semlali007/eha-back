<?php

require_once(__DIR__ . '/../../../../core/Util/DotEnv.php');

use Core\Util\DotEnv;

(new DotEnv(__DIR__ . '/../../../../.env', __DIR__ . '/../../../../.env.dev'))->load();
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
