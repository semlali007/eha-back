<?php

// charger le vendor
include_once __DIR__.'/../vendor/autoload.php'; 

// Charger le dotenv module
(new Core\util\DotEnv(__DIR__ . '/../.env'))->load(); 