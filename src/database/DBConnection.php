<?php

namespace Database;

use PDO;

class DBConnection
{

    private $dbname;
    private $host;
    private $username;
    private $password;
    private $port;
    private $pdo;
    private $schema;

    public function __construct(string $dbname, string $host, string $username, string $password, string $port, string $schema)
    {
        $this->dbname = $dbname;
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->port = $port;
        $this->schema = $schema;
    }

    public function getPDO(): PDO
    {
        return $this->pdo ?? $this->pdo = new PDO("pgsql:dbname={$this->dbname};host={$this->host};port={$this->port}", $this->username, $this->password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        ]);
    }

    public function  getSchema(): string
    {
        return $this->schema;
    }
}
