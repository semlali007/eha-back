<?php

namespace App\Security;

use RuntimeException;

/**
 * Stand-in for SimpleSAML\Auth\Simple on a developer workstation.
 *
 * GARDIAN cannot redirect back to localhost, and simplesamlphp is not
 * installed locally (the S2I build installs it from the artifact deposit),
 * so the real service provider cannot run there. Controller builds this
 * object instead of `new Simple('default-sp')` when the stub is enabled;
 * every SAML call in the application goes through Controller::getSp(), so
 * login, attributes, logout and the role checks all work unchanged.
 *
 * Enabling it takes BOTH:
 *   - SAML_LOCAL_STUB=true
 *   - ENV=locale
 * SAML_LOCAL_STUB set with any other ENV is a configuration error and is
 * refused outright rather than ignored, so a stray variable can never turn
 * authentication off on a shared environment. Neither variable is defined
 * in the CaaS deployment.
 *
 * The user is read from a JSON file, by default src/.saml-local.json (not
 * committed; see src/.saml-local.json.dist), or the path in
 * SAML_LOCAL_ATTRIBUTES. Same shape as SimpleSAML's getAttributes(): each
 * attribute maps to a list of strings.
 */
class LocalSamlStub
{
    private const LOCAL_ENV = 'locale';

    private array $attributes;

    public function __construct()
    {
        $this->attributes = self::loadAttributes();
    }

    /**
     * True when the stub must replace the real service provider.
     *
     * @throws RuntimeException when SAML_LOCAL_STUB is on outside ENV=locale
     */
    public static function isEnabled(): bool
    {
        if (!filter_var(getenv('SAML_LOCAL_STUB'), FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }
        if (getenv('ENV') !== self::LOCAL_ENV) {
            throw new RuntimeException(
                'SAML_LOCAL_STUB is only allowed with ENV=' . self::LOCAL_ENV . '; refusing to bypass SSO.'
            );
        }
        return true;
    }

    /** Same contract as SimpleSAML\Auth\Simple::getAttributes(). */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function isAuthenticated(): bool
    {
        return true;
    }

    /**
     * The local user is always authenticated, so, like the real
     * requireAuth() for an existing session, this returns and lets the
     * caller redirect.
     */
    public function requireAuth(array $params = []): void
    {
    }

    /**
     * Like SimpleSAML's logout(): redirect to ReturnTo and stop.
     *
     * @param string|array|null $params ReturnTo URL, or ['ReturnTo' => url]
     */
    public function logout(string|array|null $params = null): void
    {
        $returnTo = is_array($params) ? ($params['ReturnTo'] ?? '/') : ($params ?? '/');
        header('Location: ' . $returnTo);
        exit;
    }

    private static function loadAttributes(): array
    {
        $path = getenv('SAML_LOCAL_ATTRIBUTES');
        if (!is_string($path) || $path === '') {
            $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.saml-local.json';
        }
        if (!is_file($path)) {
            throw new RuntimeException(
                "SAML local stub: attributes file not found at {$path}. Copy src/.saml-local.json.dist to src/.saml-local.json."
            );
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (!is_array($data)) {
            throw new RuntimeException("SAML local stub: {$path} is not a JSON object.");
        }

        // SimpleSAML always returns lists; accept a bare string for convenience.
        $attributes = [];
        foreach ($data as $name => $value) {
            $attributes[$name] = array_values(array_map('strval', (array) $value));
        }
        return $attributes;
    }
}
