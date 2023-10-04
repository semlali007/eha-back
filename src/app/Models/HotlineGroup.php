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
            $query = "UPDATE {$this->table} SET living = :living, label= :label WHERE id = :id";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':living', $options['living'], PDO::PARAM_BOOL);
            $sth->bindParam(':label', $options['label'], PDO::PARAM_STR);
            $sth->bindParam(':id', $options['id'], PDO::PARAM_STR);
            return $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function get($id)
    {
        try {
            $query = 'SELECT label, living, id FROM ' . $this->table . ' WHERE id =:id';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':id', $id, PDO::PARAM_STR);
            $sth->execute();
            return  $sth->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function add($options)
    {
        try {
            $query = '
        INSERT INTO ' . $this->table . ' (label, living) VALUES (:label, :living);
        ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':label', $options['label'], PDO::PARAM_STR);
            $sth->bindParam(':living', $options['living'], PDO::PARAM_BOOL);
            $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function delete($id)
    {
        try {
            $query = "DELETE FROM {$this->table} WHERE id = :id";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':id', $id, PDO::PARAM_STR);
            return $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }
}