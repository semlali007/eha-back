<?php

// charger le vendor
require __DIR__.'/../vendor/autoload.php'; 
include __DIR__.'/../core/util/DotEnv.php';
// Charger le dotenv module
(new Core\Util\DotEnv(__DIR__ . '/../.env'))->load(); 