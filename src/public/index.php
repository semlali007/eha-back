<?php
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');

require '../vendor/autoload.php';

Logger::configure(__DIR__.'/../app/Config/logger.xml') ; // Charger le fichier de configuration

 use Core\{Router\Router,Util\DotEnv,Error\ErrorManager};

(new DotEnv(__DIR__ . '/../.env',__DIR__ . '/../.env.dev'))->load(); // Charger le dotenv module

ErrorManager::enable();//Activer la gestion centrale des erreurs et des exception
$router = new Router($_SERVER['REQUEST_URI']);

include '../app/Config/Config.php';
include '../app/Config/routing.php';// Création des routes
