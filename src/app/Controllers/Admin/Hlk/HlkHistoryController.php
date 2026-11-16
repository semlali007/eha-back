<?php

namespace App\Controllers\Admin\Hlk;

use App\Controllers\Controller;
use App\Manager\HlkSheetImportManager;
use App\Models\SyncRun;
use Core\Database\DBConnection;
use Exception;
use PDO;

/**
 * Écran admin — historique des batchs de sync HLK.
 *
 * 3 endpoints en lecture seule :
 *   - listRuns()        : liste paginée des runs
 *   - runDetail($id)    : détail complet d'un run (stats + errors + warnings)
 *   - listCreatedSheets(): fiches auto-créées, filtrable par date
 *
 * Rôle requis : ADMIN (configuré dans routing.php).
 */
class HlkHistoryController extends Controller
{
    /**
     * GET /admin/hlk/sync-runs?job=<name>&limit=50&offset=0&include_dry_runs=0
     *
     * Liste paginée de l'historique des runs.
     *
     * Query params :
     *   - job              : nom du job (défaut = 'import_sheets_from_hlk')
     *   - limit            : taille de page (défaut 50, max 200)
     *   - offset           : pagination
     *   - include_dry_runs : inclure les simulations dans la liste (défaut false)
     */
    public function listRuns()
    {
        try {
            $jobName        = $this->readJobName();
            $limit          = $this->readLimit(50, 200);
            $offset         = max(0, (int) ($_GET['offset'] ?? 0));
            $includeDryRuns = filter_var($_GET['include_dry_runs'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $syncRun = new SyncRun();
            $runs    = $syncRun->listByJob($jobName, $limit, $offset, $includeDryRuns);
            $total   = $syncRun->countByJob($jobName, $includeDryRuns);

            return $this->response([
                'job_name' => $jobName,
                'total'    => $total,
                'limit'    => $limit,
                'offset'   => $offset,
                'runs'     => $runs,
            ]);
        } catch (Exception $ex) {
            return $this->error($ex);
        }
    }

    /**
     * GET /admin/hlk/sync-runs/{id}
     *
     * Détail complet d'un run pour l'écran drill-down : toutes les stats,
     * la liste des erreurs, warnings, et les IDs des fiches créées avec
     * leur label pour un affichage direct.
     */
    public function runDetail($id)
    {
        try {
            $runId = (int) $id;
            if ($runId <= 0) {
                return $this->error(new Exception('id de run invalide'));
            }

            $syncRun = new SyncRun();
            $run     = $syncRun->findById($runId);
            if ($run === null) {
                return $this->error(new Exception('run introuvable'));
            }

            // Enrichir avec le label des fiches créées pour l'affichage direct
            // (évite au front un round-trip supplémentaire par ID).
            $run['created_sheets'] = $this->fetchSheetsByIds($run['created_sheet_ids'] ?? []);

            return $this->response($run);
        } catch (Exception $ex) {
            return $this->error($ex);
        }
    }

    /**
     * GET /admin/hlk/created-sheets?from=YYYY-MM-DD&to=YYYY-MM-DD&limit=50&offset=0
     *
     * Liste paginée des fiches auto-créées par les batchs (auto_created=true).
     *
     * Query params :
     *   - from   : date min (created_at >=), YYYY-MM-DD
     *   - to     : date max (created_at <=), YYYY-MM-DD
     *   - limit  : taille de page (défaut 50, max 200)
     *   - offset : pagination
     *
     * Retourne les colonnes clés pour un listing UI (label, status, dates,
     * achat_id_cockpit) — le détail complet passe par les endpoints fiche
     * existants.
     */
    public function listCreatedSheets()
    {
        try {
            $from   = $this->readDate($_GET['from'] ?? null);
            $to     = $this->readDate($_GET['to']   ?? null);
            $limit  = $this->readLimit(50, 200);
            $offset = max(0, (int) ($_GET['offset'] ?? 0));

            $db     = DBConnection::getInstance();
            $schema = $db->getSchema();

            $where  = ['auto_created = true'];
            $params = [];
            if ($from !== null) {
                $where[]        = 'created_at >= :from';
                $params[':from'] = $from;
            }
            if ($to !== null) {
                // +1 jour puis strict < : inclut le jour "to" complet
                $where[]      = 'created_at < :to_next';
                $params[':to_next'] = date('Y-m-d', strtotime($to . ' +1 day'));
            }
            $whereSql = implode(' AND ', $where);

            $sqlCount = "SELECT COUNT(*) FROM {$schema}sheet_headers WHERE {$whereSql}";
            $sth = $db->getPDO()->prepare($sqlCount);
            foreach ($params as $k => $v) $sth->bindValue($k, $v, PDO::PARAM_STR);
            $sth->execute();
            $total = (int) $sth->fetchColumn();

            $sqlList = "SELECT id, label, status, achat_id_cockpit, netachat_type,
                               created_at, updated_at, created_by_full_name, acheteur
                        FROM {$schema}sheet_headers
                        WHERE {$whereSql}
                        ORDER BY created_at DESC
                        LIMIT :limit OFFSET :offset";
            $sth = $db->getPDO()->prepare($sqlList);
            foreach ($params as $k => $v) $sth->bindValue($k, $v, PDO::PARAM_STR);
            $sth->bindValue(':limit',  $limit, PDO::PARAM_INT);
            $sth->bindValue(':offset', $offset, PDO::PARAM_INT);
            $sth->execute();

            return $this->response([
                'total'  => $total,
                'limit'  => $limit,
                'offset' => $offset,
                'from'   => $from,
                'to'     => $to,
                'sheets' => $sth->fetchAll(PDO::FETCH_ASSOC),
            ]);
        } catch (Exception $ex) {
            return $this->error($ex);
        }
    }

    // ==================== Helpers ====================

    private function readJobName(): string
    {
        $job = trim((string) ($_GET['job'] ?? HlkSheetImportManager::JOB_NAME));
        // Whitelist : évite les jobs arbitraires (les batchs enregistrés sont
        // connus à l'avance ; le param job sert juste à filtrer entre eux).
        return $job !== '' ? $job : HlkSheetImportManager::JOB_NAME;
    }

    private function readLimit(int $default, int $max): int
    {
        $l = (int) ($_GET['limit'] ?? $default);
        return max(1, min($max, $l));
    }

    private function readDate($value): ?string
    {
        if (!is_string($value) || $value === '') return null;
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? $value : null;
    }

    /**
     * Récupère label + status des fiches par IDs pour l'enrichissement du
     * détail de run. Retourne un array indexé par id.
     */
    private function fetchSheetsByIds(array $ids): array
    {
        if (empty($ids)) return [];

        $db          = DBConnection::getInstance();
        $schema      = $db->getSchema();
        $placeholders = [];
        $params       = [];
        foreach ($ids as $i => $id) {
            $key = ':id' . $i;
            $placeholders[] = $key;
            $params[$key]   = (int) $id;
        }
        $inClause = implode(', ', $placeholders);
        $sql = "SELECT id, label, status, achat_id_cockpit, created_at
                FROM {$schema}sheet_headers
                WHERE id IN ({$inClause})
                ORDER BY id";
        $sth = $db->getPDO()->prepare($sql);
        foreach ($params as $k => $v) $sth->bindValue($k, $v, PDO::PARAM_INT);
        $sth->execute();
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }
}
