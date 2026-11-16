<?php

namespace App\Models;

class SupplierSize extends Model
{
    protected $tableName = 'supplier_size';

    public function getAll()
    {
        try {
            $query = 'SELECT id, size as name, is_active FROM ' . $this->table . ' WHERE is_active = true ORDER BY id';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->execute();
            return $sth->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            // En cas d'erreur (table n'existe pas encore), retourner un tableau vide
            return [];
        }
    }    public function getById($id)
    {
        try {
            $query = 'SELECT id, size as name, is_active FROM ' . $this->table . ' WHERE id = :id AND is_active = true';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':id', $id, \PDO::PARAM_INT);
            $sth->execute();
            return $sth->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            // En cas d'erreur, retourner null
            return null;
        }
    }
}
