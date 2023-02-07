<?php
require_once(__DIR__ . '/../../../../core/Util/DotEnv.php');
use Core\Util\DotEnv;
(new DotEnv(__DIR__ . '/../../../../.env',__DIR__ . '/../../../../.env.dev'))->load(); // Charger le dotenv module

/**
 * SAML 2.0 remote IdP metadata for SimpleSAMLphp.
 *
 * Remember to remove the IdPs you don't use from this file.
 *
 * See: https://simplesamlphp.org/docs/stable/simplesamlphp-reference-idp-remote
 */
$metadata[getenv('SAML_IDP')] = [
    'entityid' => getenv('SAML_IDP'),
    'contacts' => [],
    'metadata-set' => 'saml20-idp-remote',
    'SingleSignOnService' => [
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
            'Location' => getenv('SAML_IDP_BASE_URL').'/gardianwebsso/SSOPOST/metaAlias/multiauth/idp6-20240420',
        ],
    ],
    'SingleLogoutService' => [
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            'Location' => getenv('SAML_IDP_BASE_URL').'/gardianwebsso/IDPSloRedirect/metaAlias/multiauth/idp6-20240420',
            'ResponseLocation' => getenv('SAML_IDP_BASE_URL').'/gardianwebsso/IDPSloRedirect/metaAlias/multiauth/idp6-20240420',
        ],
    ],
    'ArtifactResolutionService' => [
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
            'Location' => getenv('SAML_IDP_BASE_URL').'/gardianwebsso/ArtifactResolver/metaAlias/multiauth/idp6-20240420',
            'index' => 0,
            'isDefault' => true,
        ],
    ],
    'NameIDFormats' => [
        'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
        'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
        'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified',
        'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
    ],
    'keys' => [
        [
            'encryption' => false,
            'signing' => true,
            'type' => 'X509Certificate',
            'X509Certificate' => getenv('SAML_KEYS'),
        ],
    ],
];

