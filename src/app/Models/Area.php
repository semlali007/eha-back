<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use Exception;
use PDO;

class Area extends Model
{
    protected $tableName = 'zone';

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

            $query = 'SELECT DISTINCT id, name FROM ' . $this->table . ' ' . $search;
            $sth = $this->db->getPDO()->prepare($query);
            
            foreach ($params as $key => $value) {
                $sth->bindValue($key, $value, PDO::PARAM_STR);
            }
            
            $sth->execute();
            
            $rawData = $sth->fetchall(PDO::FETCH_ASSOC);
            
            // Mapper vers le format frontend {code, label}
            return array_map(function($item) {
                return [
                    'code' => (string)$item['id'],
                    'label' => $item['name']
                ];
            }, $rawData);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function getPaginatedList($options)
    {
        try {
            return $this->getSecurePaginatedList(
                $options,
                'name',
                ['id', 'name'],
                function($item) {
                    return [
                        'code' => (string)$item['id'],
                        'label' => $item['name']
                    ];
                }
            );
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function getAllSimple()
    {
        try {
            $query = 'SELECT DISTINCT id, name FROM ' . $this->table . ' ORDER BY name';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->execute();
            
            $rawData = $sth->fetchall(PDO::FETCH_ASSOC);
            
            // Retourner directement le format {code, label}
            return array_map(function($item) {
                return [
                    'code' => (string)$item['id'], // Cohérent avec les autres méthodes
                    'label' => $item['name']
                ];
            }, $rawData);
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
}
