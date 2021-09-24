<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use Exception;
use PDO;

class Committee extends Model {

    protected $tableName = 'committee';

    public function getByCode($code = null) {
        $code = trim($code);
        try {
            if (is_null($code) || empty($code)) {
                return null;
            }

            $query = 'SELECT * FROM ' . $this->table . ' WHERE code=:code';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':code', $code, $this->db->getPDO()::PARAM_INT);

            return $sth->fetch($this->db->getPDO()::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function getList() {

        try {
            $query = 'SELECT DISTINCT * FROM ' . $this->table . ' ORDER BY label asc';
            $sth = $this->db->getPDO()->prepare($query);

            return $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

}
