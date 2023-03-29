<?php
require_once(__DIR__ . '/../../../../app/Config/saml/include.php');
$idpNumber = getenv('IDP_NUMBER') ?: (getenv('IDP_NUMBER') ?: 'idp3-20240420');
$metadata[getenv('SAML_IDP')] = [
    'entityid' => getenv('SAML_IDP'),
    'contacts' => [],
    'metadata-set' => 'saml20-idp-remote',
    'SingleSignOnService' => [
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
            'Location' => getenv('SAML_IDP_BASE_URL').'/gardianwebsso/SSOPOST/metaAlias/multiauth/'.$idpNumber,
        ],
    ],
    'SingleLogoutService' => [
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            'Location' => getenv('SAML_IDP_BASE_URL').'/gardianwebsso/IDPSloRedirect/metaAlias/multiauth/'.$idpNumber,
            'ResponseLocation' => getenv('SAML_IDP_BASE_URL').'/gardianwebsso/IDPSloRedirect/metaAlias/multiauth/'.$idpNumber,
        ],
    ],
    'ArtifactResolutionService' => [
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
            'Location' => getenv('SAML_IDP_BASE_URL').'/gardianwebsso/ArtifactResolver/metaAlias/multiauth/'.$idpNumber,
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

