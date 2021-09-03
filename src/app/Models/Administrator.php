<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use Exception;
use PDO;

class Administrator extends Model
{

    protected $tableName = 'administrators';

    public function getList()
    {
        try {
            $query = "SELECT email FROM {$this->table}";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->execute();
            return $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function add($email)
    {
        try {
            $query = "INSERT INTO {$this->table} VALUES (:email, true) ";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':email', $email, PDO::PARAM_STR);
            return $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function delete($email)
    {
        try {
            $query = "DELETE FROM {$this->table} WHERE email = :email";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':email', $email, PDO::PARAM_STR);
            return $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function checkAdmin($email)
    {
        try {
            $query = 'SELECT * FROM ' . $this->table . ' WHERE email=:email and living = true ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':email', $email, PDO::PARAM_STR);
            $sth->execute();
            $result = $sth->fetch(PDO::FETCH_ASSOC);
            if (is_array($result) && $result['email'] == $email &&  $result['living'] == true) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }
}
