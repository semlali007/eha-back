<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use Exception;
use PDO;

class Committee extends Model {

    protected $tableName = 'committee';

    public function getByCode($code = null) {
        $pdo = $this->db->getPDO();
        $code = trim($code);
        try {
            if (is_null($code) || empty($code)) {
                return null;
            }

            $query = 'SELECT * FROM ' . $this->table . ' WHERE code=:code';
            $sth = $pdo->prepare($query);
            $sth->bindParam(':code', $code, $pdo::PARAM_INT);

            return $sth->fetch($pdo::FETCH_ASSOC);
        } catch (Exception $e) {
           
            echo "Failed: " . $e->getMessage();
        }
    }

    public function getList() {
        $dbh = $this->db->getPDO();
        try {
            $query = 'SELECT DISTINCT * FROM ' . $this->table . ' ORDER BY label asc';
            $sth = $dbh->prepare($query);

            return $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo "Failed: " . $e->getMessage();
        }
    }

}
