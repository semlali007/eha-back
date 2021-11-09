<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use PDO;

class News extends Model
{
    protected $tableName = 'news';


    public function getNews()
    {
        try {
            $query = 'SELECT DISTINCT * FROM ' . $this->table  . ' ORDER BY date desc';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->execute();
            return  $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }
    
    /**
     * 
     * @param type $data
     * @return type
     * @throws Exception
     */
    public function addNews(array $data) {
        try {
            $query = '
        INSERT INTO ' . $this->table . ' (title, description,date) VALUES (:title, :description, :date) ;
        ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':title', $data['title'], PDO::PARAM_STR);
            $sth->bindParam(':description', $data['description'], PDO::PARAM_STR);
            $sth->bindParam(':date', $data['date'], PDO::PARAM_STR);
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
    public function deleteNews($id) {
        try {
            $query = 'DELETE FROM ' . $this->table . ' WHERE id = :id';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            $sth->execute();
        } catch (\Exception $e) {
            throw new \Exception('erreur de suppression de contact');
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
            $sth = $this->db->getPDO()->prepare('SELECT * FROM ' . $this->table . ' WHERE id = :id');
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            $sth->execute();
            return $sth->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

}
