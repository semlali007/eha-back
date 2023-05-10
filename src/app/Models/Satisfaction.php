<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use Exception;
use PDO;

class Satisfaction extends Model {

    protected $tableName = 'satisfaction';

    public function create($options)
    {
        try {
            $query = '
            INSERT INTO ' . $this->table . ' (cockpit_id, da_pgi, accompaniement, contractual, timing, strong_points, improvement, nni) VALUES (:cockpit_id, :da_pgi, :accompaniement, :contractual, :timing, :strong_points, :improvement, :nni)
            ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':cockpit_id', $options['cockpit_id'], PDO::PARAM_STR);
            $sth->bindParam(':da_pgi', $options['da_pgi'], PDO::PARAM_STR);
            $sth->bindParam(':accompaniement', $options['accompaniement'], PDO::PARAM_STR);
            $sth->bindParam(':contractual', $options['contractual'], PDO::PARAM_STR);
            $sth->bindParam(':timing', $options['timing'], PDO::PARAM_STR);
            $sth->bindParam(':strong_points', $options['strong_points'], PDO::PARAM_STR);
            $sth->bindParam(':improvement', $options['improvement'], PDO::PARAM_STR);
            $sth->bindParam(':nni', $options['nni'], PDO::PARAM_STR);
            $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function get($nni, $cockpitId)
    {
        try {
            $query = 'SELECT * FROM ' . $this->table  . ' WHERE   cockpit_id=:cockpit_id';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':cockpit_id', $cockpitId, PDO::PARAM_STR);
            $sth->execute();
            return  $sth->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function getList()
    {
        try {
            $query = 'SELECT * FROM ' . $this->table  ;
            $sth = $this->db->getPDO()->prepare($query);
            $sth->execute();
            return  $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }
    

}
