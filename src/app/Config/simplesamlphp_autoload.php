<?php

/**
 * Register simplesamlphp's autoloader.
 *
 * simplesamlphp is NOT a composer dependency here. It is installed from the
 * artifact deposit by .s2i/bin/assemble, because the Deployment mounts
 * /opt/app-root/etc/simplesamlphp/{config,public,src}, which assumes the
 * tarball's layout rather than a vendor/ install.
 *
 * Until the move to the tarball, `simplesamlphp/simplesamlphp: ^1.19` in
 * composer.json meant vendor/autoload.php registered the SimpleSAML namespace
 * for free, everywhere. Dropping that requirement removed the autoloader with
 * it, and the first use of a SimpleSAML class died with
 *
 *     Class "SimpleSAML\Auth\Simple" not found
 *       in app/Controllers/Controller.php
 *
 * This file is wired in through composer's `autoload.files`, so every entry
 * point - public/index.php, bin/console, tests/bootstrap.php - picks it up
 * from vendor/autoload.php exactly as before.
 *
 * It is a committed shim rather than pointing `autoload.files` straight at the
 * tarball: composer resolves that list at dump time, and on a developer
 * workstation - where the tarball is absent - a missing entry breaks
 * `composer install`, which the README documents as the normal setup step.
 * Here a missing tarball simply registers nothing.
 */

(static function (): void {
    $candidates = [];

    // Explicit override first, for a non-standard install location.
    $fromEnv = getenv('SIMPLESAMLPHP_PATH');
    if (is_string($fromEnv) && $fromEnv !== '' && strtoupper($fromEnv) !== 'NULL') {
        $candidates[] = rtrim($fromEnv, '/');
    }

    // The S2I layout: the build context is src/, so the application root is
    // the contents of src/ and the tarball is unpacked alongside it.
    // __DIR__ is <root>/app/Config, so two levels up is <root>.
    $candidates[] = dirname(__DIR__, 2) . '/simplesamlphp';

    // 2.x renamed lib/ to src/; the "-full" tarball also ships its own vendor/.
    $entryPoints = ['/src/_autoload.php', '/lib/_autoload.php', '/vendor/autoload.php'];

    foreach ($candidates as $base) {
        foreach ($entryPoints as $entryPoint) {
            if (is_file($base . $entryPoint)) {
                require_once $base . $entryPoint;
                return;
            }
        }
    }
})();
