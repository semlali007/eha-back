<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use PDO;

class HotlineFil extends Model
{
    protected $tableName = 'hotline_fil';

    public function getTimeLineByQuestion($id)
    {
        try {
            $query = '
            SELECT nature,
                   quand,
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
}