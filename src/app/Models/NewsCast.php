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
            if (isset($options['status']) && $options['status'] == 1) {
                $options['published_date'] =  date('Y-m-d H:i:s');
            } else {
                $options['published_date'] =  NULL;
            }

            $query = '
        INSERT INTO ' . $this->table . ' (title, long_description, short_description, category, created_date, status, is_home, is_home_position, published_date) VALUES (:title, :description, :short_description, :category, :created_date, :status, :is_home, (select coalesce(max(is_home_position), 0) + 1 FROM ' . $this->table . '), :published_date) ON CONFLICT DO NOTHING;
        ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':title', $options['title'], PDO::PARAM_STR);
            $sth->bindParam(':description', $options['long_description'], PDO::PARAM_STR);
            $sth->bindParam(':short_description', $options['short_description'], PDO::PARAM_STR);
            $sth->bindParam(':category', $options['categoryId'], PDO::PARAM_INT);
            $sth->bindParam(':created_date', $options['created_date'], PDO::PARAM_STR);
            $sth->bindParam(':status', $options['status'], PDO::PARAM_INT);
            $sth->bindParam(':is_home', $options['is_home'], PDO::PARAM_INT);
            $sth->bindParam(':published_date', $options['published_date'], PDO::PARAM_STR);
            
            $sth->execute();
            return  $this->db->getPDO()->lastInsertId();
        } catch (\Exception $e) {
            error_log("NewsCast error: " . $e->getMessage());
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
            $perpage = $options['perpage'] ?? 50;
            $page = $options['page'] ?? 1;
            $search = ' WHERE 1 = 1 ';
            if (isset($options['category']) && !empty($options['category'])) {
                $search .= " AND  n.category = " . $options['category'];
            }
            
            $page = ($page - 1) * $perpage;
            $query = 'SELECT DISTINCT n.*, nc.label as categ,  nc.code as categ_code FROM ' . $this->table . '  AS n 
            LEFT JOIN '.(new NewsCastCategory())->table .'  AS nc 
			ON nc.id = n.category '. $search.'  ORDER BY created_date ' . ' LIMIT ' . $perpage . ' offset ' . $page;
            $sth = $this->db->getPDO()->prepare($query);
            $sth->execute();
            $data['data'] = $sth->fetchall(PDO::FETCH_ASSOC);
            $count_sql = 'SELECT COUNT(*) FROM ' . $this->table .' AS n '. $search ;
            $res = $this->db->getPDO()->query($count_sql);
            $data['total'] = $res->fetch(PDO::FETCH_ASSOC)['count'];
            return $data;
        } catch (\Exception $e) {
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
            $query = 'SELECT DISTINCT n.*, nc.label as categ, nc.code as categ_code FROM ' . $this->table . '  AS n 
            LEFT JOIN '.(new NewsCastCategory())->table .'  AS nc 
			ON nc.id = n.category  WHERE n.is_home = 1 AND n.published_date is not null ORDER BY is_home_position asc ' . ' LIMIT ' . $perpage . ' offset ' . $page;
            $sth = $this->db->getPDO()->prepare($query);
            $sth->execute();
            $data['data'] = $sth->fetchall(PDO::FETCH_ASSOC);
            $count_sql = "SELECT COUNT(*) FROM " . $this->table .' WHERE is_home = 1 AND published_date is not null  ';
            $res = $this->db->getPDO()->query($count_sql);
            $data['total'] = $res->fetch(PDO::FETCH_ASSOC)['count'];
            return $data;
        } catch (\Exception $e) {
            error_log("NewsCast error: " . $e->getMessage());
            throw new InternalServerException();
        }
    }

    public function get($id)
    {
        try {
            $query = 'SELECT n.*, nc.label as categ, nc.code as categ_code  FROM ' . $this->table . ' n LEFT JOIN '.(new NewsCastCategory())->table .'  AS nc
			ON nc.id = n.category WHERE n.id=:id';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            $sth->execute();
            return $sth->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function getPublished($id)
    {
        try {
            $query = 'SELECT n.*, nc.label as categ, nc.code as categ_code  FROM ' . $this->table . ' n LEFT JOIN '.(new NewsCastCategory())->table .'  AS nc
			ON nc.id = n.category WHERE n.id=:id AND n.status = 1';
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
            if(isset($options['status']) && $options['status'] == 1){
                    $options['published_date'] =  date('Y-m-d H:i:s');
            }else{
                $options['published_date'] =  NULL;
            }
          
            try {
                $query = '
                    UPDATE ' . $this->table . ' set title=:title, long_description=:long_description, category=:category, status=:status, published_date=:published_date, short_description=:short_description, is_home=:is_home WHERE id=:id;
            ';
                $sth = $this->db->getPDO()->prepare($query);
                $sth->bindParam(':title', $options['title'], PDO::PARAM_STR);
                $sth->bindParam(':long_description', $options['long_description'], PDO::PARAM_STR);
                $sth->bindParam(':short_description', $options['short_description'], PDO::PARAM_STR);
                $sth->bindParam(':category', $options['categoryId'], PDO::PARAM_INT);
                $sth->bindParam(':status', $options['status'], PDO::PARAM_INT);
                $sth->bindParam(':published_date', $options['published_date'], PDO::PARAM_STR);
                $sth->bindParam(':is_home', $options['is_home'], PDO::PARAM_INT);
                $sth->bindParam(':id', $options['id'], PDO::PARAM_INT);
                $sth->execute();
            } catch (\Exception $e) {
                throw new \Exception('erreur de modification de page' . $e->getMessage());
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function updatePosition(int $id, int $newPosition) {
        try {
         
                $query = '
                UPDATE ' . $this->table . ' set is_home_position=:position WHERE id=:id;
        ';
                $sth = $this->db->getPDO()->prepare($query);
                $sth->bindParam(':position', $newPosition, PDO::PARAM_INT);
                $sth->bindParam(':id', $id, PDO::PARAM_INT);
                $sth->execute();
          
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $query = "DELETE FROM {$this->table} WHERE id = :id";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            return $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }
        

    public function getPaginatedFrontList($options)
    {
        try {
            $perpage = $options['perpage'] ?? 50;
            $page = $options['page'] ?? 1;
            $search =" ";
            if (isset($options['category']) && !empty($options['category'])) {
                $search .= " AND  n.category = " . $options['category'];
            }
            
            $page = ($page - 1) * $perpage;
            $query = 'SELECT DISTINCT n.*, nc.label as categ,  nc.code as categ_code FROM ' . $this->table . '  AS n 
            LEFT JOIN '.(new NewsCastCategory())->table .'  AS nc 
			ON nc.id = n.category WHERE n.published_date is not null '. $search.'  ORDER BY published_date desc ' . ' LIMIT ' . $perpage . ' offset ' . $page;
            $sth = $this->db->getPDO()->prepare($query);
            $sth->execute();
            $data['data'] = $sth->fetchall(PDO::FETCH_ASSOC);
            $count_sql = 'SELECT COUNT(*) FROM ' . $this->table .' AS n WHERE n.published_date is not null '. $search ;
            $res = $this->db->getPDO()->query($count_sql);
            $data['total'] = $res->fetch(PDO::FETCH_ASSOC)['count'];
            return $data;
        } catch (\Exception $e) {
            exit;
            throw new InternalServerException();
        }
    }

    public function getHomePaginatedFrontList($options)
    {
        try {
            $perpage = $options['perpage'] ?? 5;
            $page = $options['page'] ?? 1;
            $page = ($page - 1) * $perpage;
            $query = 'SELECT DISTINCT n.*, nc.label as categ, nc.code as categ_code FROM ' . $this->table . '  AS n 
            LEFT JOIN '.(new NewsCastCategory())->table .'  AS nc 
			ON nc.id = n.category  WHERE n.is_home = 1 AND n.published_date is not null ORDER BY is_home_position asc ' . ' LIMIT ' . $perpage . ' offset ' . $page;
            $sth = $this->db->getPDO()->prepare($query);
            $sth->execute();
            $data['data'] = $sth->fetchall(PDO::FETCH_ASSOC);
            $count_sql = "SELECT COUNT(*) FROM " . $this->table .' WHERE is_home = 1 AND published_date is not null  ';
            $res = $this->db->getPDO()->query($count_sql);
            $data['total'] = $res->fetch(PDO::FETCH_ASSOC)['count'];
            return $data;
        } catch (\Exception $e) {
            error_log("NewsCast error: " . $e->getMessage());
            throw new InternalServerException();
        }
    }
    }
