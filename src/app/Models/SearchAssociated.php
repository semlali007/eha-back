<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use DateTime;
use PDO;

class SearchAssociated extends Model
{
    protected $tableName = 'search_associated';

    public function getList()
    {
        try {
            $query = "SELECT *  FROM {$this->table} order by id asc";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->execute();
            return $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function add($word)
    {
        try {
            $query = "INSERT INTO {$this->table}  (word) VALUES ( :title) ";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':title', $word, PDO::PARAM_STR);
            $sth->execute();
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

    public function getItem($word)
    {
        try {
            $query = "SELECT *  FROM {$this->table} WHERE unaccent(lower(word)) =:word";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':word', $word, PDO::PARAM_STR);
            $sth->execute();
            return $sth->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function update($id, $title) {

        try {
            try {
                $query = '
                UPDATE ' . $this->table . ' set word=:title WHERE id=:id;
        ';
                $sth = $this->db->getPDO()->prepare($query);
                $sth->bindParam(':title', $title, PDO::PARAM_STR);
                $sth->bindParam(':id', $id, PDO::PARAM_INT);
                $sth->execute();
            } catch (\Exception $e) {
                throw new \Exception('erreur de modification de contact' . $e->getMessage());
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
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

}
