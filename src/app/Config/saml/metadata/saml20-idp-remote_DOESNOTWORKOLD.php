<?php

/**
 * SAML 2.0 remote IdP metadata for SimpleSAMLphp.
 *
 * Remember to remove the IdPs you don't use from this file.
 *
 * See: https://simplesamlphp.org/docs/stable/simplesamlphp-reference-idp-remote
 */

$metadata['idp://REC-IDPPROXY.GARDIAN/211106'] = [
    'entityid' => 'idp://REC-IDPPROXY.GARDIAN/211106',
    'contacts' => [],
    'metadata-set' => 'saml20-idp-remote',
    'SingleSignOnService' => [
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            'Location' => 'https://rec-proxywebsso-gardian.myelectricnetwork.com:443/gardianwebsso/SSORedirect/metaAlias/gardian/proxyidp_uri',
        ],
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
            'Location' => 'https://rec-proxywebsso-gardian.myelectricnetwork.com:443/gardianwebsso/SSOPOST/metaAlias/gardian/proxyidp_uri',
        ],
    ],
    'SingleLogoutService' => [
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
            'Location' => 'https://rec-proxywebsso-gardian.myelectricnetwork.com:443/gardianwebsso/IDPSloPOST/metaAlias/gardian/proxyidp_uri',
            'ResponseLocation' => 'https://rec-proxywebsso-gardian.myelectricnetwork.com:443/gardianwebsso/IDPSloPOST/metaAlias/gardian/proxyidp_uri',
        ],
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            'Location' => 'https://rec-proxywebsso-gardian.myelectricnetwork.com:443/gardianwebsso/IDPSloRedirect/metaAlias/gardian/proxyidp_uri',
            'ResponseLocation' => 'https://rec-proxywebsso-gardian.myelectricnetwork.com:443/gardianwebsso/IDPSloRedirect/metaAlias/gardian/proxyidp_uri',
        ],
    ],
    'ArtifactResolutionService' => [],
    'NameIDFormats' => [
        'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
        'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
        'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified',
    ],
    'keys' => [
        [
            'encryption' => false,
            'signing' => true,
            'type' => 'X509Certificate',
            'X509Certificate' => 'MIIFjTCCA3WgAwIBAgICL7gwDQYJKoZIhvcNAQELBQAwZjELMAkGA1UEBhMCRlIxHjAcBgNVBAoMFUVMRUNUUklDSVRFIERFIEZSQU5DRTEXMBUGA1UECwwOMDAwMiA1NTIwODEzMTcxHjAcBgNVBAMMFUFDIElORlJBU1RSVUNUVVJFIEVERjAeFw0xOTA0MTgxNDQ3MTFaFw0yMTExMDYwOTU4MjFaMGsxCzAJBgNVBAYTAkZSMR4wHAYDVQQKDBVFTEVDVFJJQ0lURSBERSBGUkFOQ0UxFzAVBgNVBAsMDjAwMDIgNTUyMDgxMzE3MSMwIQYDVQQDDBpyZWNldHRlLWlkcC1nYXJkaWFuLmVkZi5mcjCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBALRaehDp91Kv9aaoJOI5dnvYT1hhQHeoJa/a1PJZ7cVFhI/GzTK8NzuXR8hcCAuQ9J+PtuN20uK2aWCPoMbAVWHvt9P1RAxGcP4SGI2+dsCBHn2EezLtyMoBRX9y0DChsnB58GJFo6lXHdNcyiaLIskFnfhLjXXYy1LgW9LcuTdFcjGKD2NyJMfEv9txmox4MbUAfjmdDtEarMksTZVgOxsJGfGpGu1PQpSdzadkYD9wYc0FdMEX0faZ79AAZoiCVCIErcMQ3ltyxDXYA9qf2Cooii2ZnhPLmJ0j0iWDNHip8n5P21L5SC5w9m0Q4rh8PW3EWeJeLZ8wboMjR529ATECAwEAAaOCAT4wggE6MB0GA1UdDgQWBBRj6oVnMdnw6KKfFJKMjWBY4Uz+tjAfBgNVHSMEGDAWgBTav87O9DTynD9Zv/504xKGURlThTBcBgNVHSAEVTBTMFEGDiqBegGBcAABAQIBFgEBMD8wPQYIKwYBBQUHAgEWMWh0dHA6Ly9wYy1lZGYuZWRmLmZyL3BjL2FjX2luZnJhc3RydWN0dXJlX2VkZi5wZGYwHQYDVR0lBBYwFAYIKwYBBQUHAwIGCCsGAQUFBwMBMA4GA1UdDwEB/wQEAwIFoDAlBgNVHREEHjAcghpyZWNldHRlLWlkcC1nYXJkaWFuLmVkZi5mcjBEBgNVHR8EPTA7MDmgN6A1hjNodHRwOi8vY3JsLWVkZi5lZGYuZnIvY3JsL2FjX2luZnJhc3RydWN0dXJlX2VkZi5jcmwwDQYJKoZIhvcNAQELBQADggIBACZH0flsNj+ORZmGCgd1a/ouBUYtanF5tuhOt/NYJEdAdu/zGjj3EUWvU8p/+2iNOUzqhlyac+6rFuDEAcSQA/32RahwGrfgxig5XleHCb5lXAwxZW4/uaB6uSWMTggGm+HLmH8Bklr+SwreZxQHmYCMIHWDxiedlyKl5w6ai12OKXi2TTvDWEz9OKKqCDQJJTST6G0hJGC6MYU1jrNDQ3khmPICbipEGvAhQvMFVXSDd021ong061rZRDR8/ngshJPOfWTpfQ5EGNiY79M2ynPux10EFFMQ0mGYhbn3nuJrB7NUbdBVdzJjwWUgT1mJ07mv5VeUfyu+62gCr9NuYTCD2H1Kn10gfCTv3XFBjKNS6V7434PQAoaNDFO/i1BTvvu3czsVs7pqYlJLaw6zXnXnNbeLt3PYKVpCPMQMWgl5Csi84W5xDuCia32DTccM0Xi9uLqsCYXldDsVl+9DL/9WLC5VWurzE9xcVVPRUhBfrMZ1tlYiYjZnpwFR/Jg7n/LqNzQxgcStoaM6U9AVFbEbFC0sXJUnileH6x+JYZeDnO+XaAMW4qnT3d4N+h7padvBPKdNc/ok+IsvQnvRmJ9Fbbz4NT4tETsOoa0eQVIw5HVHn0k91cNBlDz8flxqBsAnwx4SFvPW9Q4qEOOmAH5F92JUAi3ccP+6Ep4r/VoW',
        ],
    ],
];