<?php

namespace App\Models;

use PDO;
use Core\Database\DBConnection;

abstract class Model {

    protected $db;
    protected $tableName;
    protected $table;

    public function __construct()
    {
        $this->db = DBConnection::getInstance();
        $this->table = $this->db->getSchema().$this->tableName;
    }
}
