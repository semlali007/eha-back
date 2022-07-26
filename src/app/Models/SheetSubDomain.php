<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use PDO;
use Exception;

use App\Models\SubDomain;

class SheetSubDomain extends Model
{
    protected $tableName = 'sheet_subdomain';
    protected $domainTable;
    public function __construct()
    {
        parent::__construct();
        $this->subDomainTable = (new SubDomain())->table;
    }
    public function getBySheet($sheetId)
    {
        try {
            $query = 'SELECT DISTINCT  d.* FROM ' . $this->subDomainTable . ' d INNER JOIN ' . $this->table . ' sd ON sd.subdomain_id = d.id WHERE sd.sheet_id = :sheet_id';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':sheet_id', $sheetId, PDO::PARAM_INT);
            $sth->execute();
            return  $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function add($sheetId, $subDomainId)
    {
        try {
            $query = '
            INSERT INTO ' . $this->table . ' (sheet_id, subdomain_id) VALUES (:sheet_id, :subdomain_id)
             ON CONFLICT(sheet_id,subdomain_id) DO UPDATE SET subdomain_id = :subdomain_id

            ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':sheet_id', $sheetId, PDO::PARAM_INT);
            $sth->bindParam(':subdomain_id', $subDomainId, PDO::PARAM_INT);
            $sth->bindParam(':subdomain_id', $subDomainId, PDO::PARAM_INT);
            $sth->execute();
        } catch (\Exception $e) {
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
