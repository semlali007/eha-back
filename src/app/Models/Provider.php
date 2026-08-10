<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use Exception;
use PDO;

class Provider extends Model
{
    protected $tableName = 'provider';

    public function getList($options)
    {
        try {
            $search = ' WHERE 1 = 1 ';
            $params = [];

            if (isset($options) && isset($options['name']) && strlen($options['name']) > 0) {
                $search .= " AND lower(provider_label) LIKE :search_name ";
                $params[':search_name'] = '%' . strtolower($options['name']) . '%';
            }

            // LIMIT optionnel : utile pour l'autocomplete (typiquement 100) afin
            // d'éviter de charger des milliers de lignes en mémoire. Si null, pas de limite.
            $limitClause = '';
            if (isset($options['limit']) && is_int($options['limit']) && $options['limit'] > 0) {
                $limitClause = ' LIMIT ' . $options['limit'];
            }

            $query = 'SELECT DISTINCT
                        provider_code        as id,
                        provider_label       as name,
                        provider_siren       as siren,
                        provider_numero_tva  as numero_tva,
                        provider_code_naf    as code_naf
                      FROM ' . $this->table . ' ' . $search . '
                      ORDER BY provider_label' . $limitClause;
            $sth = $this->db->getPDO()->prepare($query);

            foreach ($params as $key => $value) {
                $sth->bindValue($key, $value, PDO::PARAM_STR);
            }

            $sth->execute();
            return  $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function getPaginatedList($options)
    {
        try {
            return $this->getSecurePaginatedList(
                $options,
                'provider_label',
                [
                    'provider_code        as id',
                    'provider_label       as name',
                    'provider_siren       as siren',
                    'provider_numero_tva  as numero_tva',
                    'provider_code_naf    as code_naf',
                ]
            );
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }


     public function add($options)
    {
        try {
            // Colonnes optionnelles : on construit dynamiquement l'INSERT pour
            // n'inclure que celles fournies (et non-vides), de façon à laisser
            // les valeurs NULL en BDD si non précisées.
            $columns = ['provider_label'];
            $params = [':provider_label' => $options['provider_label']];
            $optionalMap = [
                'provider_siren'      => 'provider_siren',
                'provider_numero_tva' => 'provider_numero_tva',
                'provider_code_naf'   => 'provider_code_naf',
            ];
            foreach ($optionalMap as $inputKey => $column) {
                if (isset($options[$inputKey]) && $options[$inputKey] !== '') {
                    $columns[] = $column;
                    $params[':' . $column] = $options[$inputKey];
                }
            }
            $placeholders = array_map(static fn(string $col): string => ':' . $col, $columns);

            $query = sprintf(
                'INSERT INTO %s (%s) VALUES (%s) ON CONFLICT DO NOTHING',
                $this->table,
                implode(', ', $columns),
                implode(', ', $placeholders)
            );
            $sth = $this->db->getPDO()->prepare($query);
            foreach ($params as $key => $value) {
                $sth->bindValue($key, $value, PDO::PARAM_STR);
            }
            $sth->execute();
            return  $this->db->getPDO()->lastInsertId();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function get($options)
    {
        try {
            $query = "SELECT * FROM " . $this->table . " WHERE provider_label =:provider_label";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':provider_label', $options['provider_label'], PDO::PARAM_STR);
            $sth->execute();
            return $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    /**
     * Récupère un provider par sa clé primaire. Retourne null si introuvable.
     *
     * @param int|string $code
     * @return array<string,mixed>|null
     */
    public function getByCode(int|string $code): ?array
    {
        try {
            $query = "SELECT * FROM " . $this->table . " WHERE provider_code = :code";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindValue(':code', $code, PDO::PARAM_INT);
            $sth->execute();
            $row = $sth->fetch(PDO::FETCH_ASSOC);
            return $row !== false ? $row : null;
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    /**
     * Met à jour un provider existant.
     *
     * Règles :
     *   - Seuls les champs présents dans $options sont touchés (les autres
     *     gardent leur valeur BDD actuelle).
     *   - Pour provider_label : si fourni mais vide, on ignore (NOT NULL en BDD).
     *   - Pour les fiscaux : chaîne vide → NULL (= effacement explicite).
     *
     * @param int|string             $code      provider_code à modifier
     * @param array<string,mixed>    $options   sous-ensemble de {provider_label, provider_siren,
     *                                          provider_numero_tva, provider_code_naf}
     * @return int  nombre de lignes affectées (0 si aucune)
     */
    public function update(int|string $code, array $options): int
    {
        try {
            $allowedFields = [
                'provider_label',
                'provider_siren',
                'provider_numero_tva',
                'provider_code_naf',
            ];

            $sets = [];
            $params = [':code' => $code];
            foreach ($allowedFields as $field) {
                if (!array_key_exists($field, $options)) {
                    continue;
                }
                $value = $options[$field];
                if ($field === 'provider_label') {
                    // NOT NULL en BDD : on n'autorise pas le vide
                    if ($value === null || trim((string) $value) === '') {
                        continue;
                    }
                    $sets[] = "$field = :$field";
                    $params[":$field"] = $value;
                } else {
                    // Fiscaux : chaîne vide → NULL pour permettre l'effacement
                    if ($value === '' || $value === null) {
                        $sets[] = "$field = NULL";
                    } else {
                        $sets[] = "$field = :$field";
                        $params[":$field"] = $value;
                    }
                }
            }

            if ($sets === []) {
                return 0;
            }

            $sql = sprintf(
                'UPDATE %s SET %s WHERE provider_code = :code',
                $this->table,
                implode(', ', $sets)
            );
            $sth = $this->db->getPDO()->prepare($sql);
            foreach ($params as $key => $value) {
                $type = $key === ':code' ? PDO::PARAM_INT : PDO::PARAM_STR;
                $sth->bindValue($key, $value, $type);
            }
            $sth->execute();
            return $sth->rowCount();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function delete($code)
    {

      

        try {
            $query = '
                DELETE FROM  ' . $this->table . "  WHERE provider_code = :code  
                ";

            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':code', $code, PDO::PARAM_INT);
            $sth->execute();
        } catch (\Exception $e) {
            error_log("Provider::delete error: " . $e->getMessage());
            throw new InternalServerException();
        }
    }

}
