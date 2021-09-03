<?php

namespace App\Repository;

use Database\DBConnection;

abstract class AbstractRepository {

    protected $db;

    public function __construct(DBConnection $db)
    {
        $this->db = $db;
    }
}
