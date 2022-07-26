<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use PDO;
use Exception;

use App\Models\Domain;

class SheetDomain extends Model
{
    protected $tableName = 'sheet_domain';
    protected $domainTable;
    public function __construct()
    {
        parent::__construct();
        $this->domainTable = (new Domain())->table;
    }
    public function getBySheet($sheetId)
    {
        try {
            $query = 'SELECT DISTINCT  d.* FROM ' . $this->domainTable . ' d INNER JOIN ' . $this->table . ' sd ON sd.domain_id = d.id WHERE sd.sheet_id = :sheet_id';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':sheet_id', $sheetId, PDO::PARAM_INT);
            $sth->execute();
            return  $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function add($sheetId, $domainId)
    {
        try {
            $query = '
            INSERT INTO ' . $this->table . ' (sheet_id, domain_id) VALUES (:sheet_id, :domain_id)
             ON CONFLICT(sheet_id,domain_id) DO UPDATE SET domain_id = :domain_id

            ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':sheet_id', $sheetId, PDO::PARAM_INT);
            $sth->bindParam(':domain_id', $domainId, PDO::PARAM_INT);
            $sth->bindParam(':domain_id', $domainId, PDO::PARAM_INT);
            $sth->execute();
        } catch (\Exception $e) {
            var_dump($e);exit;
            throw new InternalServerException();
        }
    }

    public function deleteGroups($sheetId, $groupsId)
    {

        if (!is_array($groupsId)  || !is_numeric($sheetId)) {
            return true;
        }

        try {
            if (count($groupsId) == 0) {
                $query = '
                DELETE FROM  ' . $this->table . "  WHERE sheet_id = :sheetId   
                ";
            } else {
                $groups = '(' . implode(',', $groupsId) . ')';
                $query = '
            DELETE FROM  ' . $this->table . "  WHERE sheet_id = :sheetId AND group_id NOT IN $groups  
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
