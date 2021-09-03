<?php

namespace App\Models\Legacy;

use App\Exceptions\InternalServerException;
use App\Models\Model;
use Exception;
use PDO;

class Legacy extends Model
{
    public function getList()
    {
        try {
            $query = "SELECT DISTINCT * FROM  $this->table  ORDER BY type_d_element ";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->execute();
            return  $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function get($id)
    {
        try {
            $query = "SELECT * FROM  $this->table  WHERE id=:id";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':id', $id, PDO::PARAM_STR);
            $sth->execute();
            return $sth->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }


    public function update($id, $data)
    {
        try {
            $query = "
                UPDATE   $this->table  set type_d_element=:type_d_element, chemin_d_acces=:chemin_d_acces WHERE id =:id";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':type_d_element', $data['type_d_element'], PDO::PARAM_STR);
            $sth->bindParam(':chemin_d_acces', $data['chemin_d_acces'], PDO::PARAM_STR);
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function initData()
    {
        try {
            $query = "DELETE FROM $this->table";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function insertData($values)
    {
        try {
            $query = "
            INSERT INTO $this->table 
            VALUES ('$values')
            ";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }
}
