<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use PDO;
use Exception;
use App\Models\SheetContractLot;


class SheetLot extends Model
{
    protected $tableName = 'sheet_lot';
    protected $contractLotTable;
    public function __construct()
    {
        parent::__construct();
        $this->contractLotTable = (new SheetContractLot())->table;
    }

    public function add($sheetId, $options)
    {
        if (!is_numeric($sheetId)) {
            return true;
        }
        
        try {
            $query = '
        INSERT INTO ' . $this->table . ' (name, description, sheet_id) VALUES (:name, :description, :sheet_id)
         ON CONFLICT(name,sheet_id) DO UPDATE SET description = :desc;
        ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':name', $options['name'], PDO::PARAM_STR);
            $sth->bindParam(':description', $options['description'], PDO::PARAM_STR);
            $sth->bindParam(':sheet_id', $sheetId, PDO::PARAM_INT);
            $sth->bindParam(':desc', $options['description'], PDO::PARAM_STR);
            $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function getBySheet($sheetId)
    {
        try {
            $query = 'SELECT DISTINCT  * FROM ' . $this->table . '  WHERE sheet_id = :sheet_id';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':sheet_id', $sheetId, PDO::PARAM_INT);
            $sth->execute();
            return  $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }


    public function deleteLots($sheetId, $lotsArray)
    {
        if (!is_array($lotsArray)  || !is_numeric($sheetId)) {
            return true;
        }

        try {

            if (count($lotsArray) == 0) {
                $query = '
                DELETE FROM  ' . $this->table . "  WHERE sheet_id = :sheetId   
                ";
                $sth = $this->db->getPDO()->prepare($query);
                $sth->bindParam(':sheetId', $sheetId, PDO::PARAM_INT);
            } else {
                // Valider et échapper chaque valeur du tableau
                $escapedLots = array_map(function($lot) {
                    return str_replace("'", "''", $lot); // Échappement PostgreSQL
                }, $lotsArray);
                $lots = implode("', '", $escapedLots);
                
                $query = '
            DELETE FROM  ' . $this->table . "  WHERE sheet_id = :sheetId AND name NOT IN ('$lots') 
            ";
                $sth = $this->db->getPDO()->prepare($query);
                $sth->bindParam(':sheetId', $sheetId, PDO::PARAM_INT);
            }
            $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }



    public function addLot($sheetId, $contractCode, $lotName)
    {
        if (!is_numeric($sheetId) || !isset($contractCode) || !isset($lotName)) {
            return true;
        }

        try {
            $query = '
        INSERT INTO ' . $this->contractLotTable . ' (sheet_id, contract_code, lot_name) VALUES (:sheet_id, :contract_code, :lot_name)
         ON CONFLICT(sheet_id, contract_code, lot_name) DO UPDATE SET lot_name = :lot;
        ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':sheet_id', $sheetId, PDO::PARAM_INT);
            $sth->bindParam(':contract_code', $contractCode, PDO::PARAM_STR);
            $sth->bindParam(':lot_name', $lotName, PDO::PARAM_STR);
            $sth->bindParam(':lot', $lotName, PDO::PARAM_STR);
            $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }



    public function getByContract($sheetId, $contractCode)
    {
        try {
            $query = 'SELECT DISTINCT  lot_name as name FROM ' . $this->contractLotTable . '  WHERE sheet_id = :sheet_id  AND contract_code = :contract_code';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':sheet_id', $sheetId, PDO::PARAM_INT);
            $sth->bindParam(':contract_code', $contractCode, PDO::PARAM_STR);
            $sth->execute();
            return  $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }


    public function deleteContractLots($sheetId, $contractCode, $lotsArray)
    {
        if (!is_array($lotsArray)  || !is_numeric($sheetId)) {
            return true;
        }

        try {

            if (count($lotsArray) == 0) {
                $query = '
                DELETE FROM  ' . $this->contractLotTable . "  WHERE sheet_id = :sheetId   AND contract_code = :contract_code
                ";
            } else {
                $lots = implode("', '", $lotsArray);
                $query = '
            DELETE FROM  ' . $this->contractLotTable . "  WHERE sheet_id = :sheetId AND contract_code = :contract_code AND lot_name NOT IN ('$lots') 
            ";
            }
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':sheetId', $sheetId, PDO::PARAM_INT);
            $sth->bindParam(':contract_code', $contractCode, PDO::PARAM_STR);
            $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }
}
