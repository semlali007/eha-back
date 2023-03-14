<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use Exception;
use PDO;

class NewsCast extends Model
{
    protected $tableName = 'newscast';

    public function create($options)
    {
        try {
            $query = '
        INSERT INTO ' . $this->table . ' (title, long_description, short_description, category, created_date, status) VALUES (:title, :description, :short_description, :category, :created_date, :status) ON CONFLICT DO NOTHING;
        ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':title', $options['title'], PDO::PARAM_STR);
            $sth->bindParam(':description', $options['long_description'], PDO::PARAM_STR);
            $sth->bindParam(':short_description', $options['short_description'], PDO::PARAM_STR);
            $sth->bindParam(':category', $options['categoryId'], PDO::PARAM_INT);
            $sth->bindParam(':created_date', $options['created_date'], PDO::PARAM_STR);
            $sth->bindParam(':status', $options['status'], PDO::PARAM_INT);
            $sth->execute();
            return  $this->db->getPDO()->lastInsertId();
        } catch (\Exception $e) {
            var_dump($e);
            exit;
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

    public function getPaginatedList($options)
    {
        try {
            $perpage = $options['perpage'] ?? 5;
            $page = $options['page'] ?? 1;
            $page = ($page - 1) * $perpage;
            $query = 'SELECT DISTINCT n.*, nc.label as categ,  nc.code as categ_code FROM ' . $this->table . '  AS n 
            LEFT JOIN '.(new NewsCastCategory())->table .'  AS nc 
			ON nc.id = n.category   ORDER BY created_date ' . ' LIMIT ' . $perpage . ' offset ' . $page;
            $sth = $this->db->getPDO()->prepare($query);
            $sth->execute();
            $data['data'] = $sth->fetchall(PDO::FETCH_ASSOC);
            $count_sql = "SELECT COUNT(*) FROM " . $this->table ;
            $res = $this->db->getPDO()->query($count_sql);
            $data['total'] = $res->fetch(PDO::FETCH_ASSOC)['count'];
            return $data;
        } catch (\Exception $e) {
            var_dump($query);
            exit;
            throw new InternalServerException();
        }
    }

    public function getHomePaginatedList($options)
    {
        try {
            $perpage = $options['perpage'] ?? 5;
            $page = $options['page'] ?? 1;
            $page = ($page - 1) * $perpage;
            $query = 'SELECT DISTINCT n.*, nc.label as categ FROM ' . $this->table . '  AS n 
            LEFT JOIN '.(new NewsCastCategory())->table .'  AS nc 
			ON nc.id = n.category  WHERE n.is_home = 1  ORDER BY is_home_position asc ' . ' LIMIT ' . $perpage . ' offset ' . $page;
            $sth = $this->db->getPDO()->prepare($query);
            $sth->execute();
            $data['data'] = $sth->fetchall(PDO::FETCH_ASSOC);
            $count_sql = "SELECT COUNT(*) FROM " . $this->table .' WHERE is_home = 1  ';
            $res = $this->db->getPDO()->query($count_sql);
            $data['total'] = $res->fetch(PDO::FETCH_ASSOC)['count'];
            return $data;
        } catch (\Exception $e) {
            var_dump($e);
            exit;
            throw new InternalServerException();
        }
    }



    public function get($id)
    {
        try {
            $query = 'SELECT n.*, nc.label as categ FROM ' . $this->table . ' n LEFT JOIN '.(new NewsCastCategory())->table .'  AS nc 
			ON nc.id = n.category WHERE n.id=:id';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            $sth->execute();
            return $sth->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }


    public function update($options)
    {
        try {
            try {
                $query = '
                    UPDATE ' . $this->table . ' set title=:title, long_description=:long_description, category=:category, status=:status, short_description=:short_description WHERE id=:id;
            ';
                $sth = $this->db->getPDO()->prepare($query);
                $sth->bindParam(':title', $options['title'], PDO::PARAM_STR);
                $sth->bindParam(':long_description', $options['long_description'], PDO::PARAM_STR);
                $sth->bindParam(':short_description', $options['short_description'], PDO::PARAM_STR);
                $sth->bindParam(':category', $options['categoryId'], PDO::PARAM_INT);
                $sth->bindParam(':status', $options['status'], PDO::PARAM_INT);
                $sth->bindParam(':id', $options['id'], PDO::PARAM_INT);
                $sth->execute();
            } catch (\Exception $e) {
                throw new \Exception('erreur de modification de page' . $e->getMessage());
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
        
    }
