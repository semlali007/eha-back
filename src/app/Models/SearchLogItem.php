<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use DateTime;
use PDO;
use App\Models\SearchAssociated;

class SearchLogItem extends Model
{
    protected $tableName = 'search_logs_items';
    protected $associatedTable;


    public function __construct()
    {
        parent::__construct();
        $this->associatedTable = (new SearchAssociated())->table;
    }

    public function getList()
    {
        try {
            $query = "
            SELECT li.*,
              CASE 
            WHEN ass.id is not NULL THEN true
            ELSE false
            END associated
            from (
            SELECT t.word, count(distinct(t.*)) as number  FROM {$this->table} as t group by t.word order by number desc )
             li LEFT JOIN {$this->associatedTable} ass ON  ass.word = li.word
             order by li.number desc
            ";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->execute();
            return $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("SearchLogItem::getList error: " . $e->getMessage());
            throw new InternalServerException();
        }
    }


    public function add($options)
    {
        try {
            $query = "INSERT INTO {$this->table}  (word, id_search) VALUES (:word, :id_search) 
          

            ";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':word', $options['word'], PDO::PARAM_STR);
            $sth->bindParam(':id_search', $options['id_search'], PDO::PARAM_INT);
            return $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
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


    public function get($id)
    {
        try {
            $query = "SELECT *  FROM {$this->table} WHERE id = :id";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            $sth->execute();
            return $sth->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

}
