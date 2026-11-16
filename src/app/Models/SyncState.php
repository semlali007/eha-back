<?php

namespace App\Models;

use PDO;

/**
 * Persistance de l'état d'avancement des batchs de synchronisation.
 *
 * Une ligne par job (identifiée par job_name). Chaque batch lit son état
 * en début de run pour connaître son curseur, et le met à jour en fin de run.
 */
class SyncState extends Model
{
    protected $tableName = 'sync_state';

    /**
     * @return array|null Le dernier état connu du job, ou null si jamais lancé.
     */
    public function getByJobName(string $jobName): ?array
    {
        $sth = $this->db->getPDO()->prepare(
            "SELECT * FROM {$this->table} WHERE job_name = :job_name"
        );
        $sth->bindValue(':job_name', $jobName, PDO::PARAM_STR);
        $sth->execute();
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /**
     * Enregistre ou met à jour l'état d'un job (upsert).
     *
     * @param string      $jobName
     * @param string|null $cursor  Curseur libre (ex: 'YYYY-MM-DD' pour signed_date)
     * @param string      $status  'success' | 'partial' | 'error'
     * @param string|null $error   Message d'erreur si status='error'
     * @param array       $stats   Compteurs à stocker en JSONB (ex: ['created'=>12, 'updated'=>3])
     */
    public function upsert(
        string $jobName,
        ?string $cursor,
        string $status,
        ?string $error = null,
        array $stats = []
    ): void {
        $sql = "
            INSERT INTO {$this->table}
                (job_name, last_run_at, last_success_at, last_cursor, last_status, last_error, stats)
            VALUES
                (:job_name, NOW(), :last_success_at, :last_cursor, :last_status, :last_error, CAST(:stats AS JSONB))
            ON CONFLICT (job_name) DO UPDATE SET
                last_run_at     = EXCLUDED.last_run_at,
                last_success_at = COALESCE(EXCLUDED.last_success_at, {$this->table}.last_success_at),
                last_cursor     = COALESCE(EXCLUDED.last_cursor,     {$this->table}.last_cursor),
                last_status     = EXCLUDED.last_status,
                last_error      = EXCLUDED.last_error,
                stats           = EXCLUDED.stats
        ";
        $sth = $this->db->getPDO()->prepare($sql);
        $sth->bindValue(':job_name',        $jobName, PDO::PARAM_STR);
        $sth->bindValue(':last_success_at', $status === 'success' ? date('c') : null);
        $sth->bindValue(':last_cursor',     $cursor);
        $sth->bindValue(':last_status',     $status, PDO::PARAM_STR);
        $sth->bindValue(':last_error',      $error);
        $sth->bindValue(':stats',           json_encode($stats, JSON_UNESCAPED_UNICODE));
        $sth->execute();
    }
}
