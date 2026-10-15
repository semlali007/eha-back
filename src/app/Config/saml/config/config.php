<?php
require_once(__DIR__ . '/../../../../app/Config/saml/include.php');
$metadataDir = __DIR__ . '/../../../../app/Config/saml/metadata';

// simplesamlphp 2.x made the Utils\* classes instantiable; in 1.x they were
// static. Calling canSetSameSiteNone() statically now fails with
// "Non-static method ... cannot be called statically". Built once here rather
// than inline at each use, so there is a single place to adjust.
$httpUtils = new \SimpleSAML\Utils\HTTP();

$config = [
	'baseurlpath' => getenv('SAML_BASE_URL_PATH'),
    'baseURL' => getenv('SAML_BASE_URL'),
    'certdir' => 'cert/',
    'loggingdir' => 'log/',
    'datadir' => 'data/',
    'tempdir' => '/tmp/simplesaml',
    'technicalcontact_name' => 'Administrator',
    'technicalcontact_email' => 'na@example.org',
    'timezone' => null,
    'secretsalt' => getenv('SAML_SECRET_SALT'),
    'auth.adminpassword' => 'garfi1234',
    'admin.protectindexpage' => false,
    'admin.protectmetadata' => false,
    'admin.checkforupdates' => true,
    'trusted.url.domains' => [],
    'trusted.url.regex' => false,
    'enable.http_post' => false,
    'assertion.allowed_clock_skew' => 180,
    'debug' => [
        'saml' => false,
        'backtraces' => true,
        'validatexml' => false,
    ],
    'showerrors' => true,
    'errorreporting' => true,
    'logging.level' => SimpleSAML\Logger::NOTICE,
    'logging.handler' => 'syslog',
    'logging.processname' => 'simplesamlphp',
    'logging.logfile' => 'simplesamlphp.log',
    'statistics.out' => [
    ],
    'proxy' => null,
    'database.dsn' => 'mysql:host=localhost;dbname=saml',
    'database.username' => 'simplesamlphp',
    'database.password' => 'secret',
    'database.options' => [],
    'database.prefix' => '',
    'database.driver_options' => [],
    'database.persistent' => false,
    'database.slaves' => [
    ],
    'enable.saml20-idp' => false,
    'enable.shib13-idp' => false,
    'enable.adfs-idp' => false,
    'shib13.signresponse' => true,
     'module.enable' => [
         'exampleauth' => false,
         'core' => true,
         'saml' => true
     ],
    'session.duration' => 8 * (60 * 60), // 8 hours.
    'session.datastore.timeout' => (4 * 60 * 60), // 4 hours
    'session.state.timeout' => (60 * 60), // 1 hour
    'session.cookie.name' => 'SimpleSAMLSessionID',
    'session.cookie.lifetime' => 0,
    'session.cookie.path' => '/',
    'session.cookie.domain' => null,
    'session.cookie.secure' => true,
    'session.cookie.samesite' => $httpUtils->canSetSameSiteNone() ? 'None' : null,
    'session.phpsession.cookiename' => 'SimpleSAML',
    'session.phpsession.savepath' => null,
    'session.phpsession.httponly' => true,
    'session.authtoken.cookiename' => 'SimpleSAMLAuthToken',
    'session.rememberme.enable' => false,
    'session.rememberme.checked' => false,
    'session.rememberme.lifetime' => (14 * 86400),
    'memcache_store.servers' => [
        [
            ['hostname' => 'localhost'],
        ],
    ],
    'memcache_store.prefix' => '',
    'memcache_store.expires' => 36 * (60 * 60), // 36 hours.
    'language' => [
        'priorities' => [
            'no' => ['nb', 'nn', 'en', 'se'],
            'nb' => ['no', 'nn', 'en', 'se'],
            'nn' => ['no', 'nb', 'en', 'se'],
            'se' => ['nb', 'no', 'nn', 'en'],
            'nr' => ['zu', 'en'],
            'nd' => ['zu', 'en'],
            'tw' => ['st', 'en'],
            'nso' => ['st', 'en'],
        ],
    ],
    'language.available' => [
        'en', 'no', 'nn', 'se', 'da', 'de', 'sv', 'fi', 'es', 'ca', 'fr', 'it', 'nl', 'lb',
        'cs', 'sl', 'lt', 'hr', 'hu', 'pl', 'pt', 'pt-br', 'tr', 'ja', 'zh', 'zh-tw', 'ru',
        'et', 'he', 'id', 'sr', 'lv', 'ro', 'eu', 'el', 'af', 'zu', 'xh', 'st',
    ],
    'language.rtl' => ['ar', 'dv', 'fa', 'ur', 'he'],
    'language.default' => 'en',
    'language.parameter.name' => 'language',
    'language.parameter.setcookie' => true,
    'language.cookie.name' => 'language',
    'language.cookie.domain' => null,
    'language.cookie.path' => '/',
    'language.cookie.secure' => true,
    'language.cookie.httponly' => false,
    'language.cookie.lifetime' => (60 * 60 * 24 * 900),
    'language.cookie.samesite' => $httpUtils->canSetSameSiteNone() ? 'None' : null,
    'attributes.extradictionary' => null,
    'theme.use' => 'default',
    'template.auto_reload' => false,
    'production' => true,
    'assets' => [
        'caching' => [
            'max_age' => 86400,
            'etag' => false,
        ],
    ],
    'idpdisco.enableremember' => true,
    'idpdisco.rememberchecked' => true,
    'idpdisco.validate' => true,
    'idpdisco.extDiscoveryStorage' => null,
    'idpdisco.layout' => 'dropdown',
    'authproc.idp' => [
        30 => 'core:LanguageAdaptor',

        45 => [
            'class'         => 'core:StatisticsWithAttribute',
            'attributename' => 'realm',
            'type'          => 'saml20-idp-SSO',
        ],
        50 => 'core:AttributeLimit',
        99 => 'core:LanguageAdaptor',
    ],
    'authproc.sp' => [
        90 => 'core:LanguageAdaptor',
    ],
    'metadatadir' => $metadataDir,
    'metadata.sources' => [
        ['type' => 'flatfile'],
    ],
    'metadata.sign.enable' => false,
    'metadata.sign.privatekey' => null,
    'metadata.sign.privatekey_pass' => null,
    'metadata.sign.certificate' => null,
    'store.type'                    => 'phpsession',
    'store.sql.dsn'                 => 'sqlite:/path/to/sqlitedatabase.sq3',
    'store.sql.username' => null,
    'store.sql.password' => null,
    'store.sql.prefix' => 'SimpleSAMLphp',
    'store.redis.host' => 'localhost',
    'store.redis.port' => 6379,
    'store.redis.prefix' => 'SimpleSAMLphp',
];