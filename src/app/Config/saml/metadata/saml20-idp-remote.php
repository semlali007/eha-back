<?php

/**
 * SAML 2.0 remote IdP metadata for SimpleSAMLphp.
 *
 * Remember to remove the IdPs you don't use from this file.
 *
 * See: https://simplesamlphp.org/docs/stable/simplesamlphp-reference-idp-remote
 */

$metadata['idp://REC-STANDARD.MULTIAUTH-SESAME-GROUPE/20240420'] = [
    'entityid' => 'idp://REC-STANDARD.MULTIAUTH-SESAME-GROUPE/20240420',
    'contacts' => [],
    'metadata-set' => 'saml20-idp-remote',
    'SingleSignOnService' => [
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            'Location' => 'https://rec-websso-gardian.myelectricnetwork.com:443/gardianwebsso/SSORedirect/metaAlias/multiauth/idp6-20240420',
        ],
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
            'Location' => 'https://rec-websso-gardian.myelectricnetwork.com:443/gardianwebsso/SSOPOST/metaAlias/multiauth/idp6-20240420',
        ],
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
            'Location' => 'https://rec-websso-gardian.myelectricnetwork.com:443/gardianwebsso/SSOSoap/metaAlias/multiauth/idp6-20240420',
        ],
    ],
    'SingleLogoutService' => [
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
            'Location' => 'https://rec-websso-gardian.myelectricnetwork.com:443/gardianwebsso/IDPSloPOST/metaAlias/multiauth/idp6-20240420',
            'ResponseLocation' => 'https://rec-websso-gardian.myelectricnetwork.com:443/gardianwebsso/IDPSloPOST/metaAlias/multiauth/idp6-20240420',
        ],
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            'Location' => 'https://rec-websso-gardian.myelectricnetwork.com:443/gardianwebsso/IDPSloRedirect/metaAlias/multiauth/idp6-20240420',
            'ResponseLocation' => 'https://rec-websso-gardian.myelectricnetwork.com:443/gardianwebsso/IDPSloRedirect/metaAlias/multiauth/idp6-20240420',
        ],
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
            'Location' => 'https://rec-websso-gardian.myelectricnetwork.com:443/gardianwebsso/IDPSloSoap/metaAlias/multiauth/idp6-20240420',
        ],
    ],
    'ArtifactResolutionService' => [
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
            'Location' => 'https://rec-websso-gardian.myelectricnetwork.com:443/gardianwebsso/ArtifactResolver/metaAlias/multiauth/idp6-20240420',
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
            'X509Certificate' => 'MIIFljCCA36gAwIBAgICB24wDQYJKoZIhvcNAQELBQAwaTELMAkGA1UEBhMCRlIxHjAcBgNVBAoMFUVMRUNUUklDSVRFIERFIEZSQU5DRTEXMBUGA1UECwwOMDAwMiA1NTIwODEzMTcxITAfBgNVBAMMGEFDIElORlJBU1RSVUNUVVJFIEVERiBHMjAeFw0yMTA0MjExNjM5NTBaFw0yNDA0MjAxNjM5NTBaMGsxCzAJBgNVBAYTAkZSMR4wHAYDVQQKDBVFTEVDVFJJQ0lURSBERSBGUkFOQ0UxFzAVBgNVBAsMDjAwMDIgNTUyMDgxMzE3MSMwIQYDVQQDDBpyZWNldHRlLWlkcC1nYXJkaWFuLmVkZi5mcjCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBALazeojqVLxI8EUfDsHn9MoA14f/XS9Ed0dWcTLBoe/DbqqSps8+JhqzlCen7ZuBYdyqNx4wnAIEiYlpgbo3IxKpeZ67hOvUcRyrHesM2QnQutPD146WWDanqjMwpiTd8uDZANpjH4W8+gfpEvAeZr/L0z6IfOjpT5eYs5qLnUwPuVt/mQnddkx2+rTT9zT7lw3I2AmT5J8xNddS0sNFWhIPCwGGyRrhliqKEuHTTnG7I3EKK2WxtWzaBfb20Y1haqRvkP9mg0i+dnyd9P5Ol7IXlqo1ncWrq2A7jCa58c5qlZdGYvQexR7rVgNiOZ5pWJ9ueKCJ0pRxIeB19/7BEYECAwEAAaOCAUQwggFAMB0GA1UdDgQWBBS4o0EuwLkT0cyEASmcgYyKG/6maDAfBgNVHSMEGDAWgBSJE24SzUttS36arh51UaAaNyGrLDBfBgNVHSAEWDBWMFQGDiqBegGBcAABAQIBGwEBMEIwQAYIKwYBBQUHAgEWNGh0dHA6Ly9wYy1lZGYuZWRmLmZyL3BjL2FjX2luZnJhc3RydWN0dXJlX2VkZl9nMi5wZGYwHQYDVR0lBBYwFAYIKwYBBQUHAwIGCCsGAQUFBwMBMA4GA1UdDwEB/wQEAwIFoDAlBgNVHREEHjAcghpyZWNldHRlLWlkcC1nYXJkaWFuLmVkZi5mcjBHBgNVHR8EQDA+MDygOqA4hjZodHRwOi8vY3JsLWVkZi5lZGYuZnIvY3JsL2FjX2luZnJhc3RydWN0dXJlX2VkZl9nMi5jcmwwDQYJKoZIhvcNAQELBQADggIBABadwPNPNpYD7Pn5/cJzR7c122N2+Y58+fsSw0NIUCu14E06QY66vonHKF2gd0YL5YwvnIWlkkizkn0Jv27EgqTktAQH4ODlaWtag9IJlfb/MCb6lNcvX1HbVIAdO1p4Fo/pvu1sCsoahZ2DWLa1F27r+zNNmKBZKujSNVm6qwceDWgMwttRnSvPzwAmvDy9N9FJqZ1h1/4OpZvY8J9raQCrnHmoMISy1VZ2lrB1HwiYPRIuJWnNfKZJ5esTOKFMFY9oXS0iVA/XUpWpakva+Sv3w5KyhDjWQXnWU7djsWZJnZrjULlBb0ex5AkP2JIVL0pRv8sqwN2Bpb1eWw5sDUEnAqqTnaZnkEOFohB3gBT+ojUo70rPunGlbbr1lZ77OL2FouzEYUrLweeP04Mz4nq+dGXDNmVYI0KubMNcfVXGnzKm3iOOkH+Hz+AW6Y6tmJJExu6qhcWWTCdcWhI/ZrGNio4eoQQ3zRfwVMWPzfacPaUCx3BBr7CJMu5jaWXqJhgTdvy5+vKGIfy7r2nfWir3+FOjDWZu0KeTBv3s/bjWj961rBFWZGNq20NCZhkhIwQKeYbRP2eptnOVbaVKD9lvAgPpyScBBuleuyoszO81Ej+jf5QNtQxzhQRA19ro+0uJ7ZRQbeYhUhOQjKvDq5mOo2sLifi2V2fM17lBKDqt',
        ],
    ],
];

