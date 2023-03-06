<?php
require_once(__DIR__ . '/../../../core/Util/DotEnv.php');
use Core\Util\DotEnv;
(new DotEnv(__DIR__ . '/../../../.env', __DIR__ . '/../../../.env.dev'))->load();
