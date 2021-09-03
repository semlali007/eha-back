<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use Exception;
use PDO;

class External extends Model
{
    protected $tableName = 'externes';

    public function getList()
    {
        try {
            $query = "SELECT externe_email, demandeur_email,date_fin  FROM {$this->table}";
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
            $query = "INSERT INTO {$this->table} VALUES (:to, :from, :end) ";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':to', $options['to'], PDO::PARAM_STR);
            $sth->bindParam(':from', $options['from'], PDO::PARAM_STR);
            $sth->bindParam(':end', $options['end'], PDO::PARAM_STR);
            return $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function delete($email)
    {
        try {
            $query = "DELETE FROM {$this->table} WHERE externe_email = :email";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':email', $email, PDO::PARAM_STR);
            return $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function checkExternalEmail($email)
    {
        $msg = 109;
        try {
            $query = "
            SELECT * FROM {$this->table} 
            WHERE externe_email = :email
        ";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':email', $email, PDO::PARAM_STR);
            $sth->execute();
            $res = $sth->fetch(PDO::FETCH_ASSOC);
            if (is_array($res) && isset($res['externe_email']) && !empty($res['externe_email'])) {
                $e = str_replace('-', '', $res['date_fin']) * 1 - date('Ymd') * 1;
                if ($e < 0) {
                    $msg = 110;
                } else {
                    $msg = true;
                }
            }

            return $msg;
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }
}
