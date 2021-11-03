<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use PDO;
use Exception;

use App\Models\Group;

class SheetGroup extends Model
{
    protected $tableName = 'sheet_groups';
    protected $groupTable;
    public function __construct()
    {
        parent::__construct();
        $this->groupTable = (new Group())->table;
    }
    public function getBySheet($sheetId)
    {
        try {
            $query = 'SELECT DISTINCT  g.* FROM ' . $this->groupTable . ' g INNER JOIN ' . $this->table . ' sg ON sg.group_id = g.id WHERE sg.sheet_id = :sheet_id';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':sheet_id', $sheetId, PDO::PARAM_INT);
            $sth->execute();
            return  $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function add($sheetId, $groupId)
    {
        try {
            $query = '
            INSERT INTO ' . $this->table . ' (sheet_id, group_id) VALUES (:sheet_id, :group_id)
             ON CONFLICT(sheet_id,group_id) DO UPDATE SET group_id = :group_id

            ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':sheet_id', $sheetId, PDO::PARAM_INT);
            $sth->bindParam(':group_id', $groupId, PDO::PARAM_INT);
            $sth->bindParam(':group_id', $groupId, PDO::PARAM_INT);
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
