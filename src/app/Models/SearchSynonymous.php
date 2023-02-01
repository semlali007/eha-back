<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use DateTime;
use PDO;

class SearchSynonymous extends Model
{
    protected $tableName = 'search_synonymous';
    public function __construct()
    {
        parent::__construct();
    }

    public function getList($id)
    {
        try {
            $query = "SELECT *  FROM {$this->table} where source =:source ";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':source', $id, PDO::PARAM_STR);
            $sth->execute();
            return $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function add($options)
    {
        try {
            $query = "INSERT INTO {$this->table}  (synonymous, source) VALUES (:synonymous, :source) ";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':synonymous', $options['title'], PDO::PARAM_STR);
            $sth->bindParam(':source', $options['id'], PDO::PARAM_INT);
            $sth->execute();
            return  $this->db->getPDO()->lastInsertId();
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

    public function getBySource($source)
    {
        try {
            $query = "SELECT *  FROM {$this->table} WHERE source = :source";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':source', $source, PDO::PARAM_INT);
            $sth->execute();
            return $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function getBySynonymous($synonymous)
    {
        try {
            $query = "SELECT distinct source  FROM {$this->table} WHERE synonymous = :synonymous";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':synonymous', $synonymous, PDO::PARAM_STR);
            $sth->execute();
            return $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    
}
