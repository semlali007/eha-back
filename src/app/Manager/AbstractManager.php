<?php

namespace App\Manager;

use Core\Database\DBConnection;

abstract class AbstractManager {

    protected $db;

    public function __construct()
    {
        $this->db = DBConnection::getInstance();
    }
}
