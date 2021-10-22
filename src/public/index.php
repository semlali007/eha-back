<?php
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');

require '../vendor/autoload.php';

Logger::configure(__DIR__.'/../app/config/logger.xml') ; // Charger le fichier de configuration

use Core\{
    Router\Router,
    Util\DotEnv
};

(new DotEnv(__DIR__ . '/../.env'))->load(); // Charger le dotenv module
$router = new Router($_SERVER['REQUEST_URI']);

include '../app/Config/Config.php';
include '../app/Config/routing.php';// Création des routes
