<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use PDO;

class Media extends Model
{
    protected $tableName = 'media';

    public function getList()
    {
        try {
            $query = "SELECT *  FROM {$this->table}";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->execute();
            return $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function add($options)
    {
        try {
            $query = "INSERT INTO {$this->table}  (name, file_name) VALUES (:name, :file_name) ";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':name', $options['name'], PDO::PARAM_STR);
            $sth->bindParam(':file_name', $options['file_name'], PDO::PARAM_STR);
            
            return $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function delete($id)
    {
        try {
            $query = "DELETE FROM {$this->table} WHERE id = :id";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            return $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }


    public function get($id)
    {
        try {
            $query = "SELECT *  FROM {$this->table} WHERE id = :id";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            $sth->execute();
            return $sth->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    

    public function update($id, $options)
    {
        try {
            $query = "UPDATE {$this->table}  set name=:name,file_name=:file_name where id=:id   ";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':name', $options['name'], PDO::PARAM_STR);
            $sth->bindParam(':file_name', $options['file_name'], PDO::PARAM_STR);
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            
            return $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

}
