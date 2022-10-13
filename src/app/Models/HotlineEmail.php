<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use Exception;
use PDO;

class HotlineEmail extends Model
{
    protected $tableName = 'hotline_email';

    public function getList()
    {
        try {
            $query = 'SELECT id as code, groupe as family, category as label  FROM ' . $this->table . ' where living = true order by groupe, category';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->execute();
            return  $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function getHotlineInformations($id)
    {
        try {
            $query = 'SELECT email, groupe, category FROM ' . $this->table . ' WHERE id =:id';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            $sth->execute();
            return  $sth->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function getAll()
    {
        try {
            $query = "SELECT id, groupe, category, email, living  FROM {$this->table}";
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
            $query = "UPDATE {$this->table} SET email = :email WHERE id = :id";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':email', $options['email'], PDO::PARAM_STR);
            $sth->bindParam(':id', $options['id'], PDO::PARAM_INT);
            return $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }
    
    public function add($options)
    {
        try {
            $query = '
        INSERT INTO ' . $this->table . ' (groupe, category, email, living) VALUES (:groupe, :category, :email, :living);
        ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':groupe', $options['group'], PDO::PARAM_STR);
            $sth->bindParam(':category', $options['category'], PDO::PARAM_STR);
            $sth->bindParam(':email', $options['email'], PDO::PARAM_STR);
            $sth->bindParam(':living', $options['living'], PDO::PARAM_BOOL);
            $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }
    
    public function livingUpdate($id)
    {
        try {
            $query = "UPDATE {$this->table} SET living = case when living is true then false else true end WHERE id = :id";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            return $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }
}
