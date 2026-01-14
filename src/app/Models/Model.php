<?php

namespace App\Models;

use PDO;
use Core\Database\DBConnection;

abstract class Model {

    protected $db;
    protected $tableName;
    protected $table;

    public function __construct()
    {
        $this->db = DBConnection::getInstance();
        $this->table = $this->db->getSchema().$this->tableName;
    }

    /**
     * Sécurise et valide une valeur entière
     * Protection contre les injections SQL via conversion stricte
     * 
     * @param mixed $value Valeur à valider
     * @param int $default Valeur par défaut si invalide
     * @return int
     */
    protected function sanitizeInt($value, int $default = 0): int
    {
        if (is_numeric($value) && (int)$value == $value) {
            return (int)$value;
        }
        return $default;
    }

    /**
     * Valide et filtre un tableau d'entiers
     * Supprime toutes les valeurs non-entières
     * 
     * @param array $values Tableau de valeurs à valider
     * @return array Tableau d'entiers valides
     */
    protected function sanitizeIntArray(array $values): array
    {
        $validValues = array_filter($values, function($value) {
            return is_numeric($value) && (int)$value == $value;
        });
        return array_map('intval', $validValues);
    }

    /**
     * Échappe une chaîne pour PostgreSQL
     * Protection contre les injections SQL dans les chaînes
     * 
     * @param string $value Valeur à échapper
     * @return string
     */
    protected function escapePgString(string $value): string
    {
        return str_replace("'", "''", $value);
    }

    /**
     * Échappe un tableau de chaînes pour PostgreSQL
     * 
     * @param array $values Tableau de chaînes
     * @return array Tableau de chaînes échappées
     */
    protected function escapePgStringArray(array $values): array
    {
        return array_map([$this, 'escapePgString'], $values);
    }

    /**
     * Construit une clause WHERE sécurisée avec recherche LIKE
     * 
     * @param string $column Nom de la colonne
     * @param string $searchValue Valeur de recherche
     * @param array &$params Référence au tableau de paramètres pour bindValue
     * @param string $paramName Nom du paramètre (optionnel)
     * @return string Clause WHERE
     */
    protected function buildLikeCondition(string $column, string $searchValue, array &$params, string $paramName = ':search'): string
    {
        $params[$paramName] = '%' . strtolower(trim($searchValue)) . '%';
        return " WHERE lower($column) LIKE $paramName ";
    }

    /**
     * Prépare et lie les paramètres de pagination sécurisés
     * 
     * @param PDOStatement $statement Statement PDO
     * @param array $options Options contenant page et perpage
     * @return array [perpage, offset]
     */
    protected function bindPaginationParams(\PDOStatement $statement, array $options): array
    {
        $perpage = $this->sanitizeInt($options['perpage'] ?? 5, 5);
        $page = $this->sanitizeInt($options['page'] ?? 1, 1);
        $offset = ($page - 1) * $perpage;
        
        $statement->bindValue(':limit', $perpage, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        return [$perpage, $offset];
    }

    /**
     * Génère une liste paginée sécurisée avec recherche optionnelle
     * Méthode générique pour tous les modèles
     * 
     * @param array $options Options de recherche et pagination
     * @param string $searchColumn Colonne pour la recherche (ex: 'name')
     * @param array $selectColumns Colonnes à sélectionner
     * @param callable|null $mapper Fonction de mapping optionnelle pour les résultats
     * @return array ['data' => [...], 'total' => int]
     */
    protected function getSecurePaginatedList(
        array $options, 
        string $searchColumn = 'name',
        array $selectColumns = ['*'],
        ?callable $mapper = null
    ): array
    {
        $where = '';
        $params = [];
        
        // Construction du WHERE sécurisé
        if (isset($options['search']) && !empty($options['search'])) {
            $where = $this->buildLikeCondition($searchColumn, $options['search'], $params);
        }
        
        // Colonnes à sélectionner
        $columns = implode(', ', $selectColumns);
        
        // Requête principale avec pagination
        $query = "SELECT DISTINCT $columns FROM {$this->table} $where ORDER BY $searchColumn LIMIT :limit OFFSET :offset";
        $sth = $this->db->getPDO()->prepare($query);
        
        // Bind des paramètres de recherche
        foreach ($params as $key => $value) {
            $sth->bindValue($key, $value, PDO::PARAM_STR);
        }
        
        // Bind des paramètres de pagination
        $this->bindPaginationParams($sth, $options);
        
        $sth->execute();
        $data['data'] = $sth->fetchAll(PDO::FETCH_ASSOC);
        
        // Appliquer le mapper si fourni
        if ($mapper !== null) {
            $data['data'] = array_map($mapper, $data['data']);
        }
        
        // Requête de comptage
        $countQuery = "SELECT COUNT(*) as count FROM {$this->table} $where";
        $st = $this->db->getPDO()->prepare($countQuery);
        
        // Bind des mêmes paramètres de recherche pour le count
        foreach ($params as $key => $value) {
            $st->bindValue($key, $value, PDO::PARAM_STR);
        }
        
        $st->execute();
        $data['total'] = $st->fetch(PDO::FETCH_ASSOC)['count'];
        
        return $data;
    }
}
