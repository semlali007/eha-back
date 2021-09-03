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
            if (isset($options) && isset($options['name']) && strlen($options['name']) > 0) {
                $search_input = strtolower($options['name']);
                $search .= " AND  lower(provider_label) LIKE '%$search_input%'  ";
            }

            $query = 'SELECT DISTINCT provider_code as id, provider_label as name FROM ' . $this->table . ' ' . $search;
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
            $query = 'SELECT DISTINCT provider_code as id, provider_label as name FROM ' . $this->table . ' ORDER BY provider_label ' . ' LIMIT ' . $perpage . ' offset ' . $page;
            $sth = $this->db->getPDO()->prepare($query);
            $sth->execute();
            $data['data'] = $sth->fetchall(PDO::FETCH_ASSOC);
            $count_sql = "SELECT COUNT(*) FROM " . $this->table;
            $res = $this->db->getPDO()->query($count_sql);
            $data['total'] = $res->fetch(PDO::FETCH_ASSOC)['count'];
            return $data;
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }


     public function add($options)
    {
        try {
            $query = '
        INSERT INTO ' . $this->table . ' (provider_code, provider_label, provider_country, provider_zipcode, provider_town, provider_siret, provider_nace, search1, search0, weight) 
         VALUES (:provider_code, :provider_label, :provider_country, :provider_zipcode, :provider_town, :provider_siret, :provider_nace, :search1, :search0, :weight)
          ON CONFLICT DO NOTHING ';
            $sth = $this->db->getPDO()->prepare($query);
            $data = [
                'provider_country' => 'France',
                'provider_zipcode' => 123556,
                'provider_town' => 'migrate',
                'provider_siret' => 123456789,
                'provider_nace' => 'migrate',
                'search1' => 'migrate',
                'search0' => 'migrate',
                'weight' => 0,
            ];

            $sth->bindParam(':provider_code', $options['provider_code'], PDO::PARAM_STR);
            $sth->bindParam(':provider_label', $options['provider_label'], PDO::PARAM_STR);
            $sth->bindParam(':provider_country', $data['provider_country'], PDO::PARAM_STR);
            $sth->bindParam(':provider_zipcode', $data['provider_zipcode'], PDO::PARAM_STR);
            $sth->bindParam(':provider_town',$data['provider_town'], PDO::PARAM_STR);
            $sth->bindParam(':provider_siret', $data['provider_siret'], PDO::PARAM_STR);
            $sth->bindParam(':provider_nace', $data['provider_nace'], PDO::PARAM_STR);
            $sth->bindParam(':search1',$data['search1'], PDO::PARAM_STR);
            $sth->bindParam(':search0',$data['search0'], PDO::PARAM_STR);
            $sth->bindParam(':weight', $data['weight'], PDO::PARAM_STR);
            $sth->execute();
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
}
