<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use Exception;
use PDO;

class Provider extends Model
{
    protected $tableName = 'provider';

    public function getList($options)
    {
        try {
            $search = ' WHERE 1 = 1 ';
            $params = [];

            if (isset($options) && isset($options['name']) && strlen($options['name']) > 0) {
                $search .= " AND lower(provider_label) LIKE :search_name ";
                $params[':search_name'] = '%' . strtolower($options['name']) . '%';
            }

            // LIMIT optionnel : utile pour l'autocomplete (typiquement 100) afin
            // d'éviter de charger des milliers de lignes en mémoire. Si null, pas de limite.
            $limitClause = '';
            if (isset($options['limit']) && is_int($options['limit']) && $options['limit'] > 0) {
                $limitClause = ' LIMIT ' . $options['limit'];
            }

            $query = 'SELECT DISTINCT provider_code as id, provider_label as name FROM '
                . $this->table . ' ' . $search . ' ORDER BY provider_label' . $limitClause;
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
            return $this->getSecurePaginatedList(
                $options,
                'provider_label',
                ['provider_code as id', 'provider_label as name']
            );
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }


     public function add($options)
    {
        try {
            $query = '
        INSERT INTO ' . $this->table . ' (provider_label) 
         VALUES (:provider_label)
          ON CONFLICT DO NOTHING ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':provider_label', $options['provider_label'], PDO::PARAM_STR);
            $sth->execute();
            return  $this->db->getPDO()->lastInsertId();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function get($options)
    {
        try {
            $query = "SELECT * FROM " . $this->table . " WHERE provider_label =:provider_label";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':provider_label', $options['provider_label'], PDO::PARAM_STR);
            $sth->execute();
            return $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function delete($code)
    {

      

        try {
            $query = '
                DELETE FROM  ' . $this->table . "  WHERE provider_code = :code  
                ";

            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':code', $code, PDO::PARAM_INT);
            $sth->execute();
        } catch (\Exception $e) {
            error_log("Provider::delete error: " . $e->getMessage());
            throw new InternalServerException();
        }
    }

}
