<?php

// charger le vendor
include_once __DIR__.'/../vendor/autoload.php'; 
$classLoader = new \Composer\Autoload\ClassLoader();

$classLoader->addPsr4("Core\Util\DotEnv\\", __DIR__, true);
$classLoader->register();
// Charger le dotenv module
(new Core\Util\DotEnv(__DIR__ . '/../.env'))->load(); 