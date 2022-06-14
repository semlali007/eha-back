<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use PDO;
use Exception;
use App\Models\Area;


class SheetArea extends Model
{
    protected $tableName = 'sheet_zones';
    protected $areaTable;
    public function __construct()
    {
        parent::__construct();
        $this->areaTable = (new Area())->table;
    }


    public function getBySheet($sheetId)
    {
        try {
            $query = 'SELECT DISTINCT  z.* FROM ' . $this->areaTable . ' z INNER JOIN ' . $this->table . ' zf ON zf.zone_id = z.id WHERE zf.sheet_id = :sheet_id ORDER BY z.name ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':sheet_id', $sheetId, PDO::PARAM_INT);
            $sth->execute();
            return  $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function add($sheetId, $zoneId)
    {
        try {
            $query = '
            INSERT INTO ' . $this->table . ' (sheet_id, zone_id) VALUES (:sheet_id, :zone_id)
             ON CONFLICT(sheet_id,zone_id) DO UPDATE SET zone_id = :zone_id

            ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':sheet_id', $sheetId, PDO::PARAM_INT);
            $sth->bindParam(':zone_id', $zoneId, PDO::PARAM_INT);
            $sth->bindParam(':zone_id', $zoneId, PDO::PARAM_INT);
            $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }


    public function deleteZones($sheetId, $zonesArray)
    {
        if (!is_array($zonesArray)  || !is_numeric($sheetId)) {
            return true;
        }
       
        try {

            if (count($zonesArray) == 0) {
                $query = '
                DELETE FROM  ' . $this->table . "  WHERE sheet_id = :sheetId   
                ";
            } else {
                $zones = '(' . implode(',', $zonesArray) . ')';
                $query = '
            DELETE FROM  ' . $this->table . "  WHERE sheet_id = :sheetId AND zone_id NOT IN $zones  
            ";
            }
          
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':sheetId', $sheetId, PDO::PARAM_INT);
            $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }
}
