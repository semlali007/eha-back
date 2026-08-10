<?php

namespace App\Services;

use Core\Database\DBConnection;
use Throwable;

/**
 * Générateurs de rapport partagés par les managers de sync HLK.
 *
 * Deux blocs communs à toute sync HLK :
 *   1. Le warning `hlk_pages_skipped` construit depuis le fetch_info du service
 *      (pages HLK qui ont hangé et qu'on a skippées) — le front l'affiche
 *      pour que le métier signale à l'équipe HLK.
 *   2. La coverage EHA : combien de fiches en base vs combien touchées par
 *      la sync. Permet au métier de juger le résultat sans dépendre uniquement
 *      des stats HLK.
 *
 * Extrait de HlkBeneficiarySyncManager pour être partagé par :
 *   - HlkBeneficiarySyncManager
 *   - HlkSheetImportManager (auto-import de fiches)
 */
class HlkSyncReport
{
    /**
     * Construit le warning `hlk_pages_skipped` à partir du fetch_info du service.
     * Retourne null s'il n'y a rien à signaler (aucune page skipée et
     * couverture 100%).
     *
     * @param array{
     *   total_pages_hlk?:int,
     *   pages_fetched?:int,
     *   pages_skipped?:int[],
     *   coverage_ratio?:float
     * } $fetchInfo Rapport de HlkSheetsService::getLastFetchInfo()
     *
     * @return array{
     *   code:string,
     *   message:string,
     *   pages_skipped:int[],
     *   total_pages_hlk:int,
     *   pages_fetched:int,
     *   coverage_ratio:float
     * }|null
     */
    public static function buildPagesSkippedWarning(array $fetchInfo): ?array
    {
        $skipped = $fetchInfo['pages_skipped'] ?? [];
        $ratio   = $fetchInfo['coverage_ratio'] ?? 1.0;
        if (empty($skipped) && $ratio >= 1.0) {
            return null;
        }
        $totalPages = (int) ($fetchInfo['total_pages_hlk'] ?? 0);
        return [
            'code'    => 'hlk_pages_skipped',
            'message' => sprintf(
                "Pages HLK ignorées (records HLK non lisibles) : %d page(s) sur %d [%s]. Les lignes de ces pages ne sont pas synchronisées. Signaler à l'équipe HLK avec ces numéros de pages.",
                count($skipped),
                $totalPages,
                implode(', ', $skipped) ?: '—'
            ),
            'pages_skipped'   => $skipped,
            'total_pages_hlk' => $totalPages,
            'pages_fetched'   => (int) ($fetchInfo['pages_fetched'] ?? 0),
            'coverage_ratio'  => (float) $ratio,
        ];
    }

    /**
     * Rapport de couverture EHA : combien de fiches en base vs combien touchées.
     * Un ratio très bas est un signal qu'il faut creuser côté HLK (pages
     * skipées probablement).
     *
     * Requête directe (pas via Sheet::) pour éviter la contrainte d'instancier
     * un modèle Sheet juste pour un COUNT.
     *
     * @return array{
     *   eha_sheets_total:int,
     *   sheets_touched:int,
     *   touched_ratio:float
     * }
     */
    public static function computeEhaCoverage(int $touchedCount): array
    {
        $total = 0;
        try {
            $db     = DBConnection::getInstance();
            $schema = $db->getSchema();
            $stmt   = $db->getPDO()->query("SELECT COUNT(*) AS c FROM {$schema}sheet_headers");
            $row    = $stmt->fetch(\PDO::FETCH_ASSOC);
            $total  = (int) ($row['c'] ?? 0);
        } catch (Throwable $e) {
            error_log('[hlk-sync-report] ⚠ coverage : COUNT(sheet_headers) échec : ' . $e->getMessage());
        }
        return [
            'eha_sheets_total' => $total,
            'sheets_touched'   => $touchedCount,
            'touched_ratio'    => $total > 0 ? round($touchedCount / $total, 3) : 0.0,
        ];
    }
}
