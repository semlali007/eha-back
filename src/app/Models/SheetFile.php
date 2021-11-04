<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use PDO;
use Exception;

class SheetFile extends Model
{
    protected $tableName = 'sheet_files';
    const TYPE = [
        'deploy_documents' => 1,
        'private_documents' => 2,
        'private_enedis_documents' => 3,
        'private_edf_enedis_documents' => 4
    ];


    public function add($sheetId, $file)
    {
        if (!isset($file['type'])) {
            $file['type'] = 0;
        }
        try {
            $query = '
        INSERT INTO ' . $this->table . ' (sheet_id, file_name, file_url, file_size, node_id, type) VALUES (:sheet_id, :file_name ,:file_url, :file_size, :node_id, :type);
        ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':sheet_id', $sheetId, PDO::PARAM_INT);
            $sth->bindParam(':file_name', $file['file_name'], PDO::PARAM_STR);
            $sth->bindParam(':file_url', $file['file_url'], PDO::PARAM_STR);
            $sth->bindParam(':file_size', $file['file_size'], PDO::PARAM_INT);
            $sth->bindParam('node_id', $file['node_id'], PDO::PARAM_STR);
            $sth->bindParam('type', $file['type'], PDO::PARAM_STR);
            $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }


    public function getBySheet($sheetId)
    {
        try {
            $query = 'SELECT * FROM ' . $this->table . ' WHERE sheet_id=:sheet_id';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':sheet_id', $sheetId, PDO::PARAM_INT);
            $sth->execute();
            return $sth->fetchAll();
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

    public function delete($id)
    {
        try {
            $query = 'DELETE FROM ' . $this->table . ' WHERE id=:id';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }
}
