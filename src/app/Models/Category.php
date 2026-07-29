<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use Exception;
use PDO;

class Category extends Model {

    protected $tableName = 'category';

    public function getByCode($code = null) {
        $code = trim($code);
        try {
            if (is_null($code) || empty($code)) {
                return null;
            }

            $query = 'SELECT * FROM ' . $this->table . ' WHERE code=:code';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':code', $code, $this->db->getPDO()::PARAM_INT);
            $sth->execute();
            return $sth->fetch($this->db->getPDO()::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function getList() {

        try {
            $query = 'SELECT DISTINCT * FROM ' . $this->table . ' ORDER BY label asc';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->execute();
            return $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    /**
     * Ajoute une nouvelle catégorie et retourne son code.
     * La colonne code étant une PK non auto-générée (pas de SERIAL/SEQUENCE),
     * on calcule le prochain code via MAX(code)+1 dans la même requête.
     * Safe pour un batch mono-thread ; pour un contexte concurrent, préférer
     * une SEQUENCE dédiée.
     *
     * Retourne le code inséré, ou null si l'insert n'a rien produit (conflit).
     */
    public function add(array $options): ?int
    {
        try {
            $query = 'INSERT INTO ' . $this->table . ' (code, label)
                      SELECT COALESCE(MAX(code), 0) + 1, :label FROM ' . $this->table . '
                      ON CONFLICT DO NOTHING
                      RETURNING code';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindValue(':label', $options['label'], PDO::PARAM_STR);
            $sth->execute();
            $row = $sth->fetch(PDO::FETCH_ASSOC);
            return isset($row['code']) ? (int) $row['code'] : null;
        } catch (\Exception $e) {
            throw new InternalServerException($e->getMessage());
        }
    }

}
