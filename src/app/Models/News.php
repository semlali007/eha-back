<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use Exception;
use PDO;

class News extends Model
{
    protected $tableName = 'news';


    public function getNews()
    {
        try {
            $query = 'SELECT DISTINCT * FROM ' . $this->table  . ' ORDER BY date desc';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->execute();
            return  $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

}
