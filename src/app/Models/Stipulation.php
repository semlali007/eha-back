<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use Exception;
use PDO;

class Stipulation extends Model
{
    protected $tableName = 'filiale';


    public function add($options)
    {
        try {
            $query = '
        INSERT INTO ' . $this->table . ' (name) VALUES (:name) ON CONFLICT DO NOTHING;
        ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':name', $options['name'], PDO::PARAM_STR);
            $sth->execute();
            return  $this->db->getPDO()->lastInsertId();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function getList($options)
    {
        try {
            $search = ' WHERE 1 = 1 ';
            $params = [];
            
            if (isset($options) && isset($options['name']) && strlen($options['name']) > 0) {
                $search .= " AND lower(name) LIKE :search_name ";
                $params[':search_name'] = '%' . strtolower($options['name']) . '%';
            }

            $query = 'SELECT DISTINCT * FROM ' . $this->table . ' ' . $search;
            $sth = $this->db->getPDO()->prepare($query);
            
            foreach ($params as $key => $value) {
                $sth->bindValue($key, $value, PDO::PARAM_STR);
            }
            
            $sth->execute();
            return  $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function getPaginatedList($options)
    {
        try {
            $options['perpage'] = $options['perpage'] ?? 10;
            return $this->getSecurePaginatedList($options, 'name');
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function get($options)
    {
        try {
            $query = 'SELECT * FROM ' . $this->table . ' WHERE name=:name';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':name', $options['name'], PDO::PARAM_STR);
            $sth->execute();
            return $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    
    public function getById($id)
    {

        try {
            $query = 'SELECT * FROM ' . $this->table . ' WHERE id=:id';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            $sth->execute();
            return $sth->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException($e->getMessage());
        }
    }

    public function delete($stipulationId)
    {

        if (!is_numeric($stipulationId)) {
            return true;
        }

        try {
            $query = '
                DELETE FROM  ' . $this->table . "  WHERE id = :id  
                ";

            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':id', $stipulationId, PDO::PARAM_INT);
            $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }
}
