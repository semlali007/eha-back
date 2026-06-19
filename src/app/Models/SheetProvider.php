<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use PDO;

use Exception;
use App\Models\Provider;
class SheetProvider extends Model
{
    protected $tableName = 'sheet_providers';
    protected $providerTable;
    public function __construct()
    {
        parent::__construct();
        $this->providerTable = (new Provider())->table;
    }

    public function getBySheet($sheetId)
    {
        try {
            $query = 'SELECT DISTINCT
                        p.provider_code       as id,
                        p.provider_label      as name,
                        p.provider_siren      as siren,
                        p.provider_numero_tva as numero_tva,
                        p.provider_code_naf   as code_naf
                      FROM ' . $this->providerTable . ' p
                      INNER JOIN ' . $this->table . ' sp ON sp.provider_code = p.provider_code
                      WHERE sp.sheet_id = :sheet_id
                      ORDER BY p.provider_label';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':sheet_id', $sheetId, PDO::PARAM_INT);
            $sth->execute();
            return  $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function add($sheetId, $providerCode)
    {
        try {
            $query = '
            INSERT INTO ' . $this->table . ' (sheet_id, provider_code) VALUES (:sheet_id, :provider_code)
             ON CONFLICT(sheet_id,provider_code) DO UPDATE SET provider_code = :provider_code

            ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':sheet_id', $sheetId, PDO::PARAM_INT);
            $sth->bindParam(':provider_code', $providerCode, PDO::PARAM_INT);
            $sth->bindParam(':provider_code', $providerCode, PDO::PARAM_INT);
            $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function deleteProviders($sheetId, $providerCodes)
    {
        if (!is_array($providerCodes)  || !is_numeric($sheetId)) {
            return true;
        }

        try {
            if (count($providerCodes) == 0) {
                $query = '
                DELETE FROM  ' . $this->table . "  WHERE sheet_id = :sheetId 
                ";
                $sth = $this->db->getPDO()->prepare($query);
                $sth->bindParam(':sheetId', $sheetId, PDO::PARAM_INT);
            } else {
                // Échapper chaque code provider
                $escapedCodes = array_map(function($code) {
                    return str_replace("'", "''", $code);
                }, $providerCodes);
                $providers = "('" . implode("','", $escapedCodes) . "')";
                
                $query = '
                DELETE FROM  ' . $this->table . "  WHERE sheet_id = :sheetId AND provider_code NOT IN  $providers
                ";
                $sth = $this->db->getPDO()->prepare($query);
                $sth->bindParam(':sheetId', $sheetId, PDO::PARAM_INT);
            }

            $res =  $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function deleteByProviderId($pId)
    {

        try {
            $query = '
                DELETE FROM  ' . $this->table . "  WHERE provider_code = :provider_code  
                ";

            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':provider_code', $pId, PDO::PARAM_INT);
            $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }
}
