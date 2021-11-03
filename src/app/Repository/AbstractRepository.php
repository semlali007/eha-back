<?php

namespace App\Repository;

use Core\Database\DBConnection;

abstract class AbstractRepository {

    protected $db;

    public function __construct()
    {
        $this->db = DBConnection::getInstance();
    }
}
