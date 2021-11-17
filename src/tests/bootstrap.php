<?php

// charger le vendor
include_once __DIR__.'/../vendor/autoload.php'; 

// Charger le dotenv module
(new Core\Util\DotEnv(__DIR__ . '/../.env'))->load(); 