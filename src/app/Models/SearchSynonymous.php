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
            date_default_timezone_set('Europe/Paris');
            $date = date('Y-m-d H:i:s');
            $query = "INSERT INTO {$this->table}  (search_text, created) VALUES (:search_text, :created) ";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':search_text', $options['search']['searchText'], PDO::PARAM_STR);
            $sth->bindParam(':created', $date, PDO::PARAM_STR);
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

}
