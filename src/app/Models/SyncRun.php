<?php

namespace App\Models;

use PDO;
use Throwable;

/**
 * Historique append-only des exécutions de batchs de sync.
 *
 * Deux usages distincts :
 *
 *   1. Write (depuis un manager de batch) :
 *      - startRun()   : insère une ligne 'running' au début, retourne son id
 *      - finishRun()  : met à jour cette ligne avec les stats + status final
 *
 *   2. Read (depuis un controller admin) :
 *      - listByJob()  : historique paginé
 *      - findById()   : détail d'un run pour l'écran de drill-down
 *      - listCreatedSheets() : fiches auto-créées, avec filtres
 *
 * Complémentaire de SyncState (qui garde le curseur du prochain run) :
 * sync_state → présent, sync_runs → passé.
 */
class SyncRun extends Model
{
    protected $tableName = 'sync_runs';

    // ==================== Writes ====================

    /**
     * Insère une ligne 'running' au début du run et retourne son id.
     * Permet à l'écran admin de voir un run en cours immédiatement.
     *
     * @return int L'id du run créé (à passer à finishRun ensuite).
     */
    public function startRun(
        string $jobName,
        string $triggeredBy,
        bool $dryRun,
        ?string $signedSinceUsed
    ): int {
        $sql = "INSERT INTO {$this->table}
                    (job_name, started_at, status, triggered_by, dry_run, signed_since_used, stats)
                VALUES
                    (:job_name, NOW(), 'running', :triggered_by, :dry_run, :signed_since_used, '{}'::jsonb)
                RETURNING id";
        $sth = $this->db->getPDO()->prepare($sql);
        $sth->bindValue(':job_name',          $jobName,        PDO::PARAM_STR);
        $sth->bindValue(':triggered_by',      $triggeredBy,    PDO::PARAM_STR);
        $sth->bindValue(':dry_run',           $dryRun,         PDO::PARAM_BOOL);
        $sth->bindValue(':signed_since_used', $signedSinceUsed, $signedSinceUsed === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $sth->execute();
        return (int) $sth->fetchColumn();
    }

    /**
     * Met à jour la ligne du run avec le résultat final.
     * Écrit finished_at (NOW()) et duration_ms côté SQL — évite les
     * incohérences si le serveur PHP et le serveur BDD ont des horloges
     * légèrement décalées.
     *
     * @param int         $runId
     * @param string      $status 'success' | 'partial' | 'error'
     * @param string|null $nextCursor Curseur écrit dans sync_state pour le prochain run
     * @param array       $stats Compteurs libres (rows_fetched, created, skipped, …)
     * @param array       $errors  Liste des erreurs row-level (peut être vide)
     * @param array       $warnings Liste des warnings (peut être vide)
     * @param int[]       $createdSheetIds IDs des fiches créées par ce run
     */
    public function finishRun(
        int $runId,
        string $status,
        ?string $nextCursor,
        array $stats,
        array $errors,
        array $warnings,
        array $createdSheetIds
    ): void {
        $sql = "UPDATE {$this->table} SET
                    finished_at       = NOW(),
                    duration_ms       = CAST(EXTRACT(EPOCH FROM (NOW() - started_at)) * 1000 AS INTEGER),
                    status            = :status,
                    next_cursor       = :next_cursor,
                    stats             = CAST(:stats AS JSONB),
                    errors            = CAST(:errors AS JSONB),
                    warnings          = CAST(:warnings AS JSONB),
                    created_sheet_ids = :created_sheet_ids
                WHERE id = :id";
        $sth = $this->db->getPDO()->prepare($sql);
        $sth->bindValue(':id',           $runId, PDO::PARAM_INT);
        $sth->bindValue(':status',       $status, PDO::PARAM_STR);
        $sth->bindValue(':next_cursor',  $nextCursor, $nextCursor === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $sth->bindValue(':stats',        json_encode($stats, JSON_UNESCAPED_UNICODE));
        $sth->bindValue(':errors',       json_encode($errors, JSON_UNESCAPED_UNICODE));
        $sth->bindValue(':warnings',     json_encode($warnings, JSON_UNESCAPED_UNICODE));

        // PostgreSQL array literal : {1,2,3}
        $arrayLiteral = empty($createdSheetIds)
            ? '{}'
            : '{' . implode(',', array_map('intval', $createdSheetIds)) . '}';
        $sth->bindValue(':created_sheet_ids', $arrayLiteral, PDO::PARAM_STR);

        $sth->execute();
    }

    /**
     * Fallback pour marquer un run comme 'error' quand une exception fatale
     * empêche l'appel normal à finishRun(). Best-effort — swallow toute
     * exception pour ne pas masquer l'erreur d'origine.
     */
    public function markAsError(int $runId, string $errorMessage, array $partialStats = []): void
    {
        try {
            $this->finishRun(
                $runId,
                'error',
                null,
                $partialStats,
                [['level' => 'fatal', 'message' => $errorMessage]],
                [],
                []
            );
        } catch (Throwable $e) {
            error_log("[sync-runs] ⚠ markAsError failed run_id={$runId} : " . $e->getMessage());
        }
    }

    // ==================== Reads ====================

    /**
     * Historique paginé pour un job donné, du plus récent au plus ancien.
     * Retourne les colonnes légères pour le listing UI ; le détail complet
     * (errors, warnings, created_sheet_ids) s'obtient via findById().
     *
     * @param bool $includeDryRuns Si false, exclut les simulations de l'historique.
     * @return array<int,array<string,mixed>>
     */
    public function listByJob(
        string $jobName,
        int $limit = 50,
        int $offset = 0,
        bool $includeDryRuns = false
    ): array {
        $sql = "SELECT id, job_name, started_at, finished_at, duration_ms, status,
                       triggered_by, dry_run, signed_since_used, next_cursor, stats
                FROM {$this->table}
                WHERE job_name = :job_name";
        if (!$includeDryRuns) {
            $sql .= " AND dry_run = false";
        }
        $sql .= " ORDER BY started_at DESC LIMIT :limit OFFSET :offset";

        $sth = $this->db->getPDO()->prepare($sql);
        $sth->bindValue(':job_name', $jobName, PDO::PARAM_STR);
        $sth->bindValue(':limit',    $limit, PDO::PARAM_INT);
        $sth->bindValue(':offset',   $offset, PDO::PARAM_INT);
        $sth->execute();

        return array_map([$this, 'decodeJsonColumns'], $sth->fetchAll(PDO::FETCH_ASSOC) ?: []);
    }

    /**
     * Compte les runs d'un job (utile pour la pagination front).
     */
    public function countByJob(string $jobName, bool $includeDryRuns = false): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE job_name = :job_name";
        if (!$includeDryRuns) {
            $sql .= " AND dry_run = false";
        }
        $sth = $this->db->getPDO()->prepare($sql);
        $sth->bindValue(':job_name', $jobName, PDO::PARAM_STR);
        $sth->execute();
        return (int) $sth->fetchColumn();
    }

    /**
     * Détail complet d'un run pour l'écran drill-down.
     * @return array<string,mixed>|null
     */
    public function findById(int $id): ?array
    {
        $sth = $this->db->getPDO()->prepare(
            "SELECT * FROM {$this->table} WHERE id = :id"
        );
        $sth->bindValue(':id', $id, PDO::PARAM_INT);
        $sth->execute();
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }
        // PostgreSQL array → PHP array
        if (isset($row['created_sheet_ids']) && is_string($row['created_sheet_ids'])) {
            $row['created_sheet_ids'] = $this->parsePgIntArray($row['created_sheet_ids']);
        }
        return $this->decodeJsonColumns($row);
    }

    // ==================== Helpers ====================

    /**
     * Convertit les colonnes JSONB (retournées en string par PDO) en tableaux PHP.
     */
    private function decodeJsonColumns(array $row): array
    {
        foreach (['stats', 'errors', 'warnings'] as $col) {
            if (isset($row[$col]) && is_string($row[$col])) {
                $decoded = json_decode($row[$col], true);
                $row[$col] = is_array($decoded) ? $decoded : null;
            }
        }
        return $row;
    }

    /**
     * Parse une string PostgreSQL "{1,2,3}" en array PHP [1, 2, 3].
     */
    private function parsePgIntArray(string $s): array
    {
        $s = trim($s, '{}');
        if ($s === '') return [];
        return array_map('intval', explode(',', $s));
    }
}
