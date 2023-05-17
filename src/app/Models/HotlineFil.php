<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use App\Models\Hotline ;
use PDO;

class HotlineFil extends Model
{
    protected $tableName = 'hotline_fil';
    protected $hotelineTable = 'hotline';
    
  
    public function getTimeLineByQuestion($id)
    {
        try {
            $query = '
            SELECT nature,
                   quand,
                   email,
                   is_hotline,
                   message 
            FROM ' . $this->table . '
            WHERE discussion = :id
            ORDER BY row ASC
           ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            $sth->execute();
            return $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function insert($options)
    {
        try {
            $query = '
            INSERT INTO ' . $this->table . ' (discussion, nature, quand, is_hotline, email, message) VALUES (:discussion, :nature, now(), :is_hotline, :email, :message)
        ';

            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':discussion', $options['discussion'], PDO::PARAM_INT);
            $sth->bindParam(':nature', $options['nature'], PDO::PARAM_STR);
            $sth->bindParam(':is_hotline', $options['is_hotline'], PDO::PARAM_BOOL);
            $sth->bindParam(':email', $options['email'], PDO::PARAM_STR);
            $sth->bindParam(':message', $options['message'], PDO::PARAM_STR);
            $sth->execute();
            return  $this->db->getPDO()->lastInsertId();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }
    /**
     * 
     * @return type
     * @throws InternalServerException
     */
    public function getALl(){
         try {
            $query = 'SELECT DISTINCT discussion as id,email_from as email,opened_at as quand, sha_hotline, notified FROM ' . $this->hotelineTable . ' WHERE ORDER BY discussion ASC ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->execute();
            return $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException($e->getMessage());
        }
    }
    public function isCommented(int $questionId){
          try {
            $query = 'SELECT *  FROM ' . $this->table . ' WHERE discussion = :id and nature=\'O\' ORDER BY row ASC ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':id', $questionId, PDO::PARAM_INT);
            $sth->execute();
            return $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException($e->getMessage());
        }
    }
    
    
    public function updateNotif(){
         $query = "
            UPDATE {$this->hotelineTable} SET notified = notified + 1
        ";

            $sth = $this->db->getPDO()->prepare($query);
            $sth->execute();
            return  $this->db->getPDO()->lastInsertId();
    }
}