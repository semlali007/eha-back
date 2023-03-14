<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use PDO;


class SheetLogs extends Model
{
    protected $tableName = 'sheets_logs';
    public function __construct()
    {
        parent::__construct();
    }


    public function add($options)
    {
        try {
            $query = '
            INSERT INTO ' . $this->table . ' (version, id_source, changement_fields, old_value, new_value, date) VALUES (1, :id_source, :changement_fields, :old_value, :new_value, :date) ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':id_source', $options['source'], PDO::PARAM_STR);
            $sth->bindParam(':changement_fields', $options['fields'], PDO::PARAM_STR);
            $sth->bindParam(':old_value', $options['old'], PDO::PARAM_STR);
            $sth->bindParam(':new_value', $options['new'], PDO::PARAM_STR);
            $sth->bindParam(':date', $options['date'], PDO::PARAM_STR);
            $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function getBySheet($sheetId)
    {
        try {
            $query = 'SELECT DISTINCT  * FROM ' . $this->table . '  WHERE id_source = :id_source ORDER BY date desc ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':id_source', $sheetId, PDO::PARAM_INT);
            $sth->execute();
            return  $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

}
