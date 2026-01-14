<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use Exception;
use PDO;

class SubDomain extends Model
{
    protected $tableName = 'subdomain';

    public function add($options)
    {
        try {
        
            $type = $options['type'] ?? 'datp';
            $query = '
        INSERT INTO ' . $this->table . ' (name, type) VALUES (:name, :type) ON CONFLICT DO NOTHING;
        ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':name', $options['name'], PDO::PARAM_STR);
            $sth->bindParam(':type',$type, PDO::PARAM_STR);
            $sth->execute();
            return  $this->db->getPDO()->lastInsertId();
        } catch (\Exception $e) {
            var_dump($e);exit;
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
            
            // Bind parameters
            foreach ($params as $key => $value) {
                $sth->bindValue($key, $value, PDO::PARAM_STR);
            }
            
            $sth->execute();
            return  $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

   
    public function get($options)
    {
        try {
            $query = ' SELECT * FROM ' . $this->table . ' WHERE name=:name AND type=:type ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':name', $options['name'], PDO::PARAM_STR);
            $sth->bindParam(':type', $options['type'], PDO::PARAM_STR);
            $sth->execute();
            return $sth->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }
    public function getPaginatedList($options)
    {
        try {
            return $this->getSecurePaginatedList($options, 'name');
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }
}
