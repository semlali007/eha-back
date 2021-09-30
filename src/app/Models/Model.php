<?php

namespace App\Models;

use PDO;
use Database\DBConnection;

abstract class Model {

    protected $db;
    protected $tableName;
    protected $table;

    public function __construct()
    {
        $this->db = new DBConnection();
        $this->table = $this->db->getSchema().$this->tableName;
    }
}
