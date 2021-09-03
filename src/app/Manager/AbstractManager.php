<?php

namespace App\Manager;

use Database\DBConnection;

abstract class AbstractManager {

    protected $db;

    public function __construct(DBConnection $db)
    {
        $this->db = $db;
    }
}
