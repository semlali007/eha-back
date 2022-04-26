<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use Exception;
use PDO;

class SheetBeneficiary extends Model
{
    protected $tableName = 'sheet_filiales';
    protected $beneficiaryTable;

    public function __construct()
    {
        parent::__construct();
        $this->beneficiaryTable = (new Beneficiary())->table;
    }

    public function add($sheetId, $filialeId)
    {
        try {
            $query = '
            INSERT INTO ' . $this->table . ' (sheet_id, filiale_id) VALUES (:sheet_id, :filiale_id)
             ON CONFLICT(sheet_id,filiale_id) DO UPDATE SET filiale_id = :filiale_id

            ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':sheet_id', $sheetId, PDO::PARAM_INT);
            $sth->bindParam(':filiale_id', $filialeId, PDO::PARAM_INT);
            $sth->bindParam(':filiale_id', $filialeId, PDO::PARAM_INT);
            $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }


    public function getBySheet($sheetId)
    {
        try {
            $query = 'SELECT DISTINCT  f.* FROM ' . $this->beneficiaryTable . ' f INNER JOIN ' . $this->table . ' sf ON sf.filiale_id = f.id WHERE sf.sheet_id = :sheet_id';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':sheet_id', $sheetId, PDO::PARAM_INT);
            $sth->execute();
            return  $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }
  


    public function deleteFiliales($sheetId, $filialesArray)
    {

        if (!is_array($filialesArray)  || !is_numeric($sheetId)) {
            return true;
        }

        try {
            if (count($filialesArray) == 0) {
                $query = '
                DELETE FROM  ' . $this->table . "  WHERE sheet_id = :sheetId  
                ";
            } else {
                $filiales = '(' . implode(',', $filialesArray) . ')';
                $query = '
                DELETE FROM  ' . $this->table . "  WHERE sheet_id = :sheetId AND filiale_id NOT IN $filiales  
                ";
            }

            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':sheetId', $sheetId, PDO::PARAM_INT);
            $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function deleteByBeneficiaryId($beneficiaryId)
    {

        if (!is_numeric($beneficiaryId)) {
            return true;
        }

        try {
            $query = '
                DELETE FROM  ' . $this->table . "  WHERE filiale_id = :filiale_id  
                ";

            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':filiale_id', $beneficiaryId, PDO::PARAM_INT);
            $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }
    
}
