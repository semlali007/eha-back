<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use Exception;
use PDO;

class HotlineGroup extends Model
{
    protected $tableName = 'hotline_groupe';

    public function getList()
    {
        try {
            $query = 'SELECT id as code, label  FROM ' . $this->table . ' where living = true order by label';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->execute();
            return  $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function getAll()
    {
        try {
            $query = "SELECT id, label, living  FROM {$this->table}";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->execute();
            return  $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function update($options)
    {
        try {
            $query = "UPDATE {$this->table} SET living = :living, label= :label WHERE id = :id";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':living', $options['living'], PDO::PARAM_BOOL);
            $sth->bindParam(':label', $options['label'], PDO::PARAM_STR);
            $sth->bindParam(':id', $options['id'], PDO::PARAM_STR);
            return $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function get($id)
    {
        try {
            $query = 'SELECT label, living, id FROM ' . $this->table . ' WHERE id =:id';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':id', $id, PDO::PARAM_STR);
            $sth->execute();
            return  $sth->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function add($options)
    {
        try {
            $labelWords = explode(' ', $options['label']);
            $initials = '';

            // Règle 1 : Un seul mot
            if (count($labelWords) === 1) {
                $initials = strtoupper(substr($labelWords[0], 0, 2));
            }
            // Règle 2 : Deux mots
            elseif (count($labelWords) === 2) {
                $initials = strtoupper(substr($labelWords[0], 0, 1) . substr($labelWords[1], 0, 1));
            }
            // Règle 3 : Trois mots
            elseif (count($labelWords) === 3) {
                $initials = strtoupper(substr($labelWords[0], 0, 1) . substr($labelWords[2], 0, 1));
            }

            // si un enregistrement avec ID existe déjà
            $existingRecord = $this->get($initials);

            if ($existingRecord) {
                // enregistrement avec ID existe déjà, le prochain numéro
                $counter = 1;
                $maxCounter = 9999;
                while ($this->get($initials . $counter)) {
                    if ($counter >= $maxCounter) {
                        throw new InternalServerException();
                    }
                    $counter++;
                }

                // Ajouter le compteur à l'ID
                $options['id'] = $initials . $counter;
            } else {
                // L'ID n'existe pas, utilise simplement les initiales
                $options['id'] = $initials;
            }

            // Insérer avec l'ID généré
            $query = '
                INSERT INTO ' . $this->table . ' (id, label, living) VALUES (:id, :label, :living);
            ';

            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':id', $options['id'], PDO::PARAM_STR);
            $sth->bindParam(':label', $options['label'], PDO::PARAM_STR);
            $sth->bindParam(':living', $options['living'], PDO::PARAM_BOOL);
            $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function delete($id)
    {
        try {
            $query = "DELETE FROM {$this->table} WHERE id = :id";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':id', $id, PDO::PARAM_STR);
            return $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }
}