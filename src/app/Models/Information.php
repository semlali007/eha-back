<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use PDO;

class Information extends Model
{
    protected $tableName = 'informations';


    public function getContent($type)
    {
        try {
            $query = 'SELECT * FROM ' . $this->table  . ' WHERE type=:type AND start_date <= CURRENT_TIMESTAMP and end_date>=CURRENT_TIMESTAMP order by id desc ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':type', $type, PDO::PARAM_STR);
            $sth->execute();
            return  $sth->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }
  
    /**
     * 
     * @param array $data
     * @throws Exception
     */
    public function update($id, array $data)
    {
        try {
            try {
                $query = '
                UPDATE ' . $this->table . ' set short_description=:short_description, long_description=:long_description, start_date=:start_date, end_date=:end_date WHERE id=:id;
        ';
                $sth = $this->db->getPDO()->prepare($query);
                $sth->bindParam(':id', $id, PDO::PARAM_INT);
                $sth->bindParam(':short_description', $data['short_description'], PDO::PARAM_STR);
                $sth->bindParam(':long_description', $data['long_description'], PDO::PARAM_STR);
                $sth->bindParam(':start_date', $data['start_date'], PDO::PARAM_STR);
                $sth->bindParam(':end_date', $data['end_date'], PDO::PARAM_STR);
                $sth->execute();
            } catch (\Exception $e) {
                throw new \Exception('erreur de modification de page' . $e->getMessage());
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }


        /**
     * 
     * @param type $data
     * @return type
     * @throws Exception
     */
    public function add($type, array $data) {
        try {
            $query = '
        INSERT INTO ' . $this->table . ' (type, short_description, long_description, start_date, end_date) VALUES (:type, :short_description, :long_description, :start_date, :end_date) ;
        ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':type', $type, PDO::PARAM_STR);
            $sth->bindParam(':short_description', $data['short_description'], PDO::PARAM_STR);
            $sth->bindParam(':long_description', $data['long_description'], PDO::PARAM_STR);
            $sth->bindParam(':start_date', $data['start_date'], PDO::PARAM_STR);
            $sth->bindParam(':end_date', $data['end_date'], PDO::PARAM_STR);
            $sth->execute();
            return $this->db->getPDO()->lastInsertId();
        } catch (\Exception $e) {
            throw new \Exception('erreur d\'ajout de contact' . $e->getMessage());
        }
    }

    /**
     * 
     * @param array $data
     * @throws Exception
     */
    public function updateNews(array $data) {
        try {
            try {
                $query = '
                UPDATE ' . $this->table . ' set title=:title, description=:description, date=:date WHERE id=:id;
        ';
                $sth = $this->db->getPDO()->prepare($query);
                $sth->bindParam(':title', $data['title'], PDO::PARAM_STR);
                $sth->bindParam(':description', $data['description'], PDO::PARAM_STR);
                $sth->bindParam(':date', $data['date'], PDO::PARAM_STR);
                $sth->bindParam(':id', $data['id'], PDO::PARAM_INT);
                $sth->execute();
            } catch (\Exception $e) {
                throw new \Exception('erreur de modification de contact' . $e->getMessage());
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * 
     * @param type $id
     * @throws Exception
     */
    public function delete($id) {
        try {
            $query = 'DELETE FROM ' . $this->table . ' WHERE id = :id';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            $sth->execute();
        } catch (\Exception $e) {
            throw new \Exception('erreur de suppression de contact');
        }
    }


    
    
    public function getList($type)
    {
        try {
            $query =  "SELECT  * FROM {$this->table} WHERE type=:type ORDER BY start_date asc";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':type', $type, PDO::PARAM_INT);
            $sth->execute();
            return  $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

 /**
     * 
     * @param type $id
     * @return type
     * @throws Exception
     */
    public function getElement($id) {
        try {
            $sth = $this->db->getPDO()->prepare("SELECT  * FROM {$this->table} WHERE id=:id" );
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            $sth->execute();
            return $sth->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

}
