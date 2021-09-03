<?php

header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');

require '../vendor/autoload.php';

use Core\Http\Request ;
use Core\{
    Router\Router,
    Util\DotEnv
};

(new DotEnv(__DIR__ . '/../.env'))->load(); // Charger le dotenv module
$router = new Router(Request::get('url'));

include '../app/config/config.php' ;
include '../app/Config/init_env.php' ;
include '../app/Config/routing.php';// Création des routes
