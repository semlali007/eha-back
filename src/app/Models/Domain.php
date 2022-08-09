<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use Exception;
use PDO;

class Domain extends Model
{
    protected $tableName = 'domain';

    public function add($options)
    {
        try {
            $query = '
        INSERT INTO ' . $this->table . ' (name, type) VALUES (:name, :type) ON CONFLICT DO NOTHING;
        ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':name', $options['name'], PDO::PARAM_STR);
            $sth->bindParam(':type', $options['type'], PDO::PARAM_STR);
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
            if (isset($options) && isset($options['name']) && strlen($options['name']) > 0) {
                $search_input = strtolower($options['name']);
                $search .= " AND  lower(name) LIKE '%$search_input%'  ";
            }

            $query = 'SELECT DISTINCT * FROM ' . $this->table . ' ' . $search;
            $sth = $this->db->getPDO()->prepare($query);
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
            $perpage = $options['perpage'] ?? 5;
            $page = $options['page'] ?? 1;
            $page = ($page - 1) * $perpage;
            $query = 'SELECT DISTINCT * FROM ' . $this->table . ' ORDER BY name ' . ' LIMIT ' . $perpage . ' offset ' . $page;
            $sth = $this->db->getPDO()->prepare($query);
            $sth->execute();
            $data['data'] = $sth->fetchall(PDO::FETCH_ASSOC);
            $count_sql = "SELECT COUNT(*) FROM " . $this->table;
            $st = $this->db->getPDO()->prepare($count_sql);
            $st->execute();
            $data['total'] = $st->fetch(PDO::FETCH_ASSOC)['count'];
            return $data;
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }
    
}
