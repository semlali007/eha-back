<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use PDO;
use Database\DBConnection;
use Exception;


class SheetOption extends Model
{
    protected $tableName = 'sheet_options';


    public function getBySheet($sheetId)
    {
        try {
            $query = 'SELECT DISTINCT  so.* FROM ' . $this->table . ' so  WHERE so.sheet_id = :sheet_id order by so.id';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':sheet_id', $sheetId, PDO::PARAM_INT);
            $sth->execute();
            return  $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function add($data)
    {
        try {
            $query = '
            INSERT INTO ' . $this->table . ' (sheet_id, start_date, end_date) VALUES (:sheet_id, :start_date, :end_date)
            ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':sheet_id', $data['sheet_id'], PDO::PARAM_INT);
            $sth->bindParam(':start_date', $data['startdate'], PDO::PARAM_STR);
            $sth->bindParam(':end_date', $data['enddate'], PDO::PARAM_STR);
            $sth->execute();
            return  $this->db->getPDO()->lastInsertId();
        } catch (\Exception $e) {
            throw new InternalServerException($e->getMessage());
        }
    }
  
    public function update($data)
    {
        try {
            $query = "UPDATE {$this->table} SET start_date=:start_date,end_date=:end_date WHERE id = :id";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':id', $data['id'], PDO::PARAM_INT);
            $sth->bindParam(':start_date', $data['startdate'], PDO::PARAM_STR);
            $sth->bindParam(':end_date', $data['enddate'], PDO::PARAM_STR);
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
}
