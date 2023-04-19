<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use Exception;
use PDO;

class NewsCastConfig extends Model
{
    protected $tableName = 'newscast_config';

    public function getByConfigLabel(string $config): array
    {
        try {
            $query = "SELECT config_value  FROM {$this->table} where config_label= :config_label";
            $sth = ($this->db->getPDO()->prepare($query));
            $sth->bindParam(':config_label', $config, \PDO::PARAM_STR);
            $sth->execute();
            return $sth->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException($e->getMessage());
        }
    }

    public function updateByConfig(string $configLabel, $configValue)
    {
        try {
            $query = '
                UPDATE ' . $this->table . ' set config_value=:config_value WHERE config_label=:config_label;
        ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':config_value', $configValue, PDO::PARAM_STR);
            $sth->bindParam(':config_label', $configLabel, PDO::PARAM_STR);
            $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException($e->getMessage());
        }
    }
}
