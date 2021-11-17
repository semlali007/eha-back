<?php

// charger le vendor
require __DIR__.'/../vendor/autoload.php'; 

use Core\Util\DotEnv ;
// Charger le dotenv module
(new DotEnv(__DIR__ . '/../.env'))->load(); 