<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use Exception;
use PDO;

class HotlineGroup extends Model
{
    protected $tableName = 'hotline_groupe';

    public function getList()
    {
        try {
            $query = 'SELECT id as code, label  FROM ' . $this->table . ' where living = true order by label';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->execute();
            return  $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function getAll()
    {
        try {
            $query = "SELECT id, label, living  FROM {$this->table}";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->execute();
            return  $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function update($options)
    {
        try {
            $query = "UPDATE {$this->table} SET living = case when living is true then false else true end WHERE id = :id";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':id', $options['id'], PDO::PARAM_INT);
            return $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }
}
