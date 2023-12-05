<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use PDO;

class Subscription extends Model
{
    protected $tableName ='subscription';

    public function add($sheetId, $userNni)
    {
        try {
            $query = '
        INSERT INTO ' . $this->table . ' (sheet_id, user_nni) VALUES (:sheet_id, :user_nni) ON CONFLICT DO NOTHING;
        ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':sheet_id', $sheetId, PDO::PARAM_INT);
            $sth->bindParam(':user_nni', $userNni, PDO::PARAM_STR);
            $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function isSubscribed($sheetId, $nni)
    {
        try {
            $query = 'SELECT DISTINCT  * FROM ' . $this->table . '  WHERE sheet_id = :sheet_id AND user_nni=:user_nni';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':sheet_id', $sheetId, PDO::PARAM_INT);
            $sth->bindParam(':user_nni', $nni, PDO::PARAM_STR);
            $sth->execute();
            return  $sth->rowCount() > 0;
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }



    public function unsubscribe($sheetId, $userNni)
    {
        try {
            $query = '
        INSERT INTO ' . $this->table . ' (sheet_id, user_nni) VALUES (:sheet_id, :user_nni) ON CONFLICT DO NOTHING;
        ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':sheet_id', $sheetId, PDO::PARAM_INT);
            $sth->bindParam(':user_nni', $userNni, PDO::PARAM_STR);
            $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function getSubscribers($sheetId)
    {
        try {
            $query = 'SELECT DISTINCT user_nni FROM ' . $this->table . '  WHERE sheet_id = :sheet_id';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':sheet_id', $sheetId, PDO::PARAM_INT);
            $sth->execute();
            return  $sth->rowCount() > 0;
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function remove($sheetId, $userNni)
    {
        try {
            $query = "DELETE FROM {$this->table} WHERE sheet_id = :sheet_id AND user_nni=:user_nni";

            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':sheet_id', $sheetId, PDO::PARAM_INT);
            $sth->bindParam(':user_nni', $userNni, PDO::PARAM_STR);
            return $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }


}
