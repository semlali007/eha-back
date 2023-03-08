<?php
require_once(__DIR__ . '/../../../../app/Config/saml/include.php');
$metadata[getenv('SAML_IDP')] = [
    'entityid' => getenv('SAML_IDP'),
    'contacts' => [],
    'metadata-set' => 'saml20-idp-remote',
    'SingleSignOnService' => [
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
            'Location' => getenv('SAML_IDP_BASE_URL').'/gardianwebsso/SSOPOST/metaAlias/multiauth/'.getenv('IDP_NUMBER'),
        ],
    ],
    'SingleLogoutService' => [
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            'Location' => getenv('SAML_IDP_BASE_URL').'/gardianwebsso/IDPSloRedirect/metaAlias/multiauth/'.getenv('IDP_NUMBER'),
            'ResponseLocation' => getenv('SAML_IDP_BASE_URL').'/gardianwebsso/IDPSloRedirect/metaAlias/multiauth/'.getenv('IDP_NUMBER'),
        ],
    ],
    'ArtifactResolutionService' => [
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
            'Location' => getenv('SAML_IDP_BASE_URL').'/gardianwebsso/ArtifactResolver/metaAlias/multiauth/'.getenv('IDP_NUMBER'),
            'index' => 0,
            'isDefault' => true,
        ],
    ],
    'NameIDFormats' => [
        'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
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

