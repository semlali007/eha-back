<?php

require __DIR__.'/../vendor/autoload.php'; // charger le vendor
use Core\Util\DotEnv ;

$pathEnv = __DIR__ . '/../.env';
(new DotEnv($pathEnv))->load(); // Charger le dotenv module