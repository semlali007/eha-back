<?php

namespace Core\Database;

use PDO;

class DBConnection {

    private $dbname;
    private $host;
    private $username;
    private $password;
    private $port;
    private $pdo;
    private $schema;

    /**
     * @var LoggerHandler
     * @access private
     * @static
     */
    private static $_instance = null;

    public function __construct() {
        $this->dbname = getenv("DB_NAME");
        $this->host = getenv("DB_HOST");
        $this->username = getenv("DB_USER");
        $this->password = getenv("DB_PWD");
        $this->port = getenv("DB_PORT");
        $this->schema = getenv("DB_SCHEMA");
    }

    public function getPDO(): PDO {
        return $this->pdo ?? $this->pdo = new PDO("pgsql:dbname={$this->dbname};host={$this->host};port={$this->port}", $this->username, $this->password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        ]);
    }

    /**
     * Méthode qui crée l'unique instance de la classe
     * si elle n'existe pas encore puis la retourne.
     *
     * @return self|null
     */
    public static function getInstance(): ?self {

        if (is_null(self::$_instance)) {
            self::$_instance = new DBConnection();
        }

        return self::$_instance;
    }

    /**
     * 
     * @return string
     */
    public function getSchema(): string {
        return $this->schema;
    }

}
