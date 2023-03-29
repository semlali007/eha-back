<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use Exception;
use PDO;

class NewsCastCategory extends Model
{
    protected $tableName = 'newscast_category';

   

    public function getList()
    {
        try {
           
            $query = 'SELECT DISTINCT * FROM ' . $this->table ;
            $sth = $this->db->getPDO()->prepare($query);
            $sth->execute();
            return  $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }
  
}
