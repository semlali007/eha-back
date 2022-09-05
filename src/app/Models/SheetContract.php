<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use PDO;
use Database\DBConnection;
use Exception;
use App\Models\Contract;


class SheetContract extends Model
{

    protected $tableName = 'sheet_contracts';
    protected $contractTable;
    public function __construct()
    {
        parent::__construct();
        $this->contractTable = (new Contract())->table;
    }


    public function add($sheetId, $contract)
    {
        try {
            $query = '
            INSERT INTO ' . $this->table . ' (sheet_id, contract_code, provider, contacts) VALUES (:sheet_id, :contract_code, :provider, :contacts)
             ON CONFLICT(sheet_id,contract_code) DO UPDATE SET provider = :prvdr, contacts = :contacts

            ';

            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':sheet_id', $sheetId, PDO::PARAM_INT);
            $sth->bindParam(':contract_code', $contract['contract_code'], PDO::PARAM_STR);
            $sth->bindParam(':provider', $contract['provider'], PDO::PARAM_STR);
            $sth->bindParam(':contacts',  $contract['contacts'], PDO::PARAM_STR);
            $sth->bindParam(':prvdr',  $contract['provider'], PDO::PARAM_STR);
            $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function update($id, $contractCode)
    {

        try {
            $query = '
            UPDATE ' . $this->table . ' SET contract_code = :contract_code WHERE id = :id
            ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':contract_code', $contractCode, PDO::PARAM_STR);
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function getBySheet($sheetId)
    {
        try {
            $query = 'SELECT DISTINCT  sc.contract_code,sc.provider,sc.contacts, c.contract_code as exist_contract_code,string_agg(DISTINCT concat(c.contract_system,\'__\',c.contract_system_label), \'|\') FROM ' . $this->table . ' sc LEFT JOIN ' . $this->contractTable . ' c ON c.contract_code = sc.contract_code WHERE sheet_id = :sheet_id GROUP BY sc.contract_code,sc.provider, sc.contacts, c.contract_code ORDER BY sc.contract_code ' ;
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':sheet_id', $sheetId, PDO::PARAM_INT);
            $sth->execute();
            return  $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    
    public function deleteBySheet($sheetId)
    {
        try {
            $query = 'DELETE FROM ' . $this->table . ' WHERE sheet_id=:sheet_id';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':sheet_id', $sheetId, PDO::PARAM_INT);
            $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function deleteContracts($sheetId, $contractsArray)
    {
        if (!is_array($contractsArray)  || !is_numeric($sheetId)) {
            return true;
        }

        $contractsCode = [];
        foreach($contractsArray as $contract){
            $contractsCode[] = $contract['contract_code'];
        }

        try {
            if (count($contractsCode) == 0) {
                $query = '
                DELETE FROM  ' . $this->table . '  WHERE sheet_id = :sheetId
                ';
                $sth = $this->db->getPDO()->prepare($query);
                $sth->bindParam(':sheetId', $sheetId, PDO::PARAM_INT);
            } else {
                $contracts = implode(', ', $contractsCode);
                $query = '
            DELETE FROM  ' . $this->table . '  WHERE sheet_id = :sheetId AND contract_code NOT IN (:contracts) 
            ';
                $sth = $this->db->getPDO()->prepare($query);
                $sth->bindParam(':sheetId', $sheetId, PDO::PARAM_INT);
                $sth->bindParam(':contracts', $contracts, PDO::PARAM_STR);
            }

            $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }
    
}
