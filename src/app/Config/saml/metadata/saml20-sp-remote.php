<?php
require_once(__DIR__ . '/../../../../core/Util/DotEnv.php');

use Core\Util\DotEnv;

(new DotEnv(__DIR__ . '/../../../../.env', __DIR__ . '/../../../../.env.dev'))->load(); // Charger le dotenv module

/**
 * SAML 2.0 remote SP metadata for SimpleSAMLphp.
 *
 * See: https://simplesamlphp.org/docs/stable/simplesamlphp-reference-sp-remote
 */

/*
 * Example SimpleSAMLphp SAML 2.0 SP
 */

$metadata[getenv('SAML_SP_META_ID')] = [
    'entityid' => getenv('SAML_SP_META_ID'),
    'contacts' => [],
    'metadata-set' => 'saml20-sp-remote',
    'AssertionConsumerService' => [
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
            'Location' => getenv('SAML_SP_BASE_URL').'/gardianwebsso/Consumer/metaAlias/gardian/proxysp_uri-202420',
            'index' => 0,
            'isDefault' => true,
        ],
    ],
    'SingleLogoutService' => [
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            'Location' => getenv('SAML_SP_BASE_URL').'/gardianwebsso/SPSloRedirect/metaAlias/gardian/proxysp_uri-202420',
            'ResponseLocation' => getenv('SAML_SP_BASE_URL').'/gardianwebsso/SPSloRedirect/metaAlias/gardian/proxysp_uri-202420',
        ],
    ],
    'NameIDFormat' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
    'keys' => [
        [
            'encryption' => false,
            'signing' => true,
            'type' => 'X509Certificate',
            'X509Certificate' => getenv('SAML_KEYS'),
        ],
    ],
    'validate.authnrequest' => false,
    'saml20.sign.assertion' => true,
];
