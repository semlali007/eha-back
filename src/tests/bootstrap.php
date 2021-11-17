<?php

// charger le vendor
require __DIR__.'/../vendor/autoload.php'; 

$classLoader = new \Composer\Autoload\ClassLoader();
$classLoader->addPsr4("core\\", __DIR__, true);
$classLoader->register();
// Charger le dotenv module
(new core\Util\DotEnv(__DIR__ . '/../.env'))->load(); 