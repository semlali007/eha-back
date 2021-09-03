<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use PDO;

class HotlineFile extends Model
{
    protected $tableName = 'hotline_fichiers';

    public function insert($options)
    {
        try {
            $query = '
            INSERT INTO ' . $this->table . ' (discussion, file_code, file_name) VALUES (:discussion, :file_code, :file_name)
        ';

            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':discussion', $options['discussion'], PDO::PARAM_INT);
            $sth->bindParam(':file_code', $options['file_code'], PDO::PARAM_STR);
            $sth->bindParam(':file_name', $options['file_name'], PDO::PARAM_STR);
            $sth->execute();
            return  $this->db->getPDO()->lastInsertId();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }
}