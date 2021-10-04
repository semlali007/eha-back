<?php

namespace App\Models;

use PDO;
use Database\DBConnection;

abstract class Model {

    protected $db;
    protected $tableName;
    protected $table;

    public function __construct(DBConnection $db)
    {
        $this->db = $db;
        $this->table = $db->getSchema().$this->tableName;
    }
}
