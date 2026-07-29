<?php

namespace App\Manager;

use App\Models\Sheet;
use App\Models\SheetBeneficiary;
use App\Models\SheetStipulation;
use App\Security\User;
use App\Services\HlkSheetsMapper;
use App\Services\HlkSheetsService;
use App\Services\HlkSyncReport;
use Throwable;

/**
 * Auto-import des fiches marché depuis HLK.
 *
 * Rejoué (batch nocturne ou trigger manuel) : chaque exécution rapatrie les
 * marchés HLK depuis un curseur `signed_date`, les agrège via HlkSheetsMapper
 * (1 fiche = 1 achat_id_cockpit), puis pour chaque fiche :
 *   1. Lookup par `achat_id_cockpit` (idempotence canonique)
 *   2. Sinon, lookup secondaire par `contract_code` (rattrape les fiches
 *      créées manuellement avant que le batch existe)
 *   3. Si trouvée : merge des bénéficiaires (union), backfill de
 *      `achat_id_cockpit` si absent
 *   4. Si absente : création complète en brouillon via `SheetManager::add()`
 *
 * Le manager s'appuie sur le kernel HLK partagé :
 *   - HlkSheetsService  : stream + skip-on-fail pagination
 *   - HlkSheetsMapper   : agrégation cartésien → fiches
 *   - HlkSyncReport     : warning `hlk_pages_skipped`
 *
 * Retourne un rapport structuré identique en forme à HlkBeneficiarySyncManager
 * (dry_run, stats, warnings, fetch_info) pour un affichage unifié côté front.
 *
 * ⚠ Utilisation typique : appelé par la command CLI ImportSheetsFromHlk,
 * qui persiste `signed_since` en curseur dans `sync_state` et rejoue.
 */
class HlkSheetImportManager
{
    /** NNI utilisé pour l'audit (created_by / updated_by) des fiches créées par le batch. */
    private const BATCH_USER_NNI      = 'HLK_BATCH';
    private const BATCH_USER_FULLNAME = 'HLK Batch Import';

    /**
     * @param bool $dryRun Si true : aucune écriture BDD, uniquement rapport.
     * @param string|null $signedSince Curseur `signed_date` HLK. Null = pas de filtre.
     *
     * @return array {
     *   dry_run:bool, signed_since:?string,
     *   pages_processed:int, rows_fetched:int, sheets_aggregated:int,
     *   created:int, updated:int, matched_by_contract:int, backfilled:int,
     *   filiales_added:int, filiales0_added:int,
     *   errors:array[], warnings:array[], fetch_info:?array,
     *   duration_ms:int, started_at:string, finished_at:string,
     *   category_usage:array
     * }
     */
    public function run(bool $dryRun = false, ?string $signedSince = null): array
    {
        $started = new \DateTimeImmutable();
        $t0 = microtime(true);

        $stats = [
            'dry_run'             => $dryRun,
            'signed_since'        => $signedSince,
            'pages_processed'     => 0,
            'rows_fetched'        => 0,
            'sheets_aggregated'   => 0,
            'created'             => 0,
            'updated'             => 0,
            'matched_by_contract' => 0,
            'backfilled'          => 0,
            'filiales_added'      => 0,
            'filiales0_added'     => 0,
            'errors'              => [],
            'warnings'            => [],
        ];

        error_log('[hlk-sheet-import] ================================================================');
        error_log('[hlk-sheet-import] Démarrage import ' . ($dryRun ? '[DRY-RUN]' : '[REAL]') . ' | signed_since=' . ($signedSince ?? '(none)'));

        $service = new HlkSheetsService();
        $allRows = [];

        try {
            // === PHASE 1 : Stream HLK page par page (skip-on-fail) ==============
            // Mapper::transform() a besoin de TOUTES les rows pour grouper par
            // achat_id_cockpit (un même achat peut couvrir plusieurs pages).
            // On accumule donc en mémoire avant de mapper.
            foreach ($service->streamAllPages(HlkSheetsService::DEFAULT_PAGE_LIMIT, $signedSince) as $pageNum => $pageRows) {
                $stats['pages_processed']++;
                $stats['rows_fetched'] += count($pageRows);
                foreach ($pageRows as $row) {
                    $allRows[] = $row;
                }
                error_log(sprintf(
                    '[hlk-sheet-import] page=%d reçue (%d rows, cumul=%d)',
                    $pageNum, count($pageRows), $stats['rows_fetched']
                ));
            }
        } catch (Throwable $e) {
            $stats['errors'][] = [
                'level' => 'fatal_fetch',
                'message' => $e->getMessage(),
                'after_page' => $stats['pages_processed'],
            ];
            error_log("[hlk-sheet-import] ⚠ FATAL fetch : {$e->getMessage()}");
        }

        // === PHASE 2 : Agrégation via mapper ===============================
        $mapper = new HlkSheetsMapper();
        try {
            $sheets = $mapper->transform($allRows);
            $stats['sheets_aggregated'] = count($sheets);
            $stats['category_usage']    = $mapper->getCategoryUsage();
        } catch (Throwable $e) {
            $stats['errors'][] = [
                'level'   => 'fatal_map',
                'message' => $e->getMessage(),
            ];
            error_log("[hlk-sheet-import] ⚠ FATAL map : {$e->getMessage()}");
            $sheets = [];
        }

        // === PHASE 3 : Ingestion fiche par fiche ============================
        $sheetModel      = new Sheet();
        $sheetManager    = new SheetManager();
        $benefRelation   = new SheetBeneficiary();
        $stipRelation    = new SheetStipulation();
        $batchUser       = $this->buildBatchUser();

        foreach ($sheets as $sheetData) {
            try {
                $this->ingestSheet(
                    $sheetData, $dryRun,
                    $sheetModel, $sheetManager, $benefRelation, $stipRelation, $batchUser,
                    $stats
                );
            } catch (Throwable $e) {
                $stats['errors'][] = [
                    'achat_id_cockpit' => $sheetData['achat_id_cockpit'] ?? null,
                    'message'          => $e->getMessage(),
                ];
                error_log(sprintf(
                    '[hlk-sheet-import] ⚠ erreur ingest fiche achat_id=%s : %s',
                    $sheetData['achat_id_cockpit'] ?? '?',
                    $e->getMessage()
                ));
            }
        }

        // === Rapport final =================================================
        $fetchInfo = $service->getLastFetchInfo();
        if ($fetchInfo !== null) {
            $stats['fetch_info'] = $fetchInfo;
            $skipWarning = HlkSyncReport::buildPagesSkippedWarning($fetchInfo);
            if ($skipWarning !== null) {
                $stats['warnings'][] = $skipWarning;
            }
        }

        $finished = new \DateTimeImmutable();
        $stats['duration_ms'] = (int) round((microtime(true) - $t0) * 1000);
        $stats['started_at']  = $started->format(\DateTimeInterface::ATOM);
        $stats['finished_at'] = $finished->format(\DateTimeInterface::ATOM);

        error_log(sprintf(
            '[hlk-sheet-import] terminé %s en %dms | pages=%d rows=%d sheets=%d | created=%d updated=%d (via_contract=%d backfilled=%d) | filiales=%d filiales0=%d | errors=%d warnings=%d',
            $dryRun ? '[DRY-RUN]' : '[REAL]',
            $stats['duration_ms'],
            $stats['pages_processed'], $stats['rows_fetched'], $stats['sheets_aggregated'],
            $stats['created'], $stats['updated'],
            $stats['matched_by_contract'], $stats['backfilled'],
            $stats['filiales_added'], $stats['filiales0_added'],
            count($stats['errors']), count($stats['warnings'])
        ));
        error_log('[hlk-sheet-import] ================================================================');

        return $stats;
    }

    /**
     * Ingère UNE fiche (issue du mapper) : lookup existant → CREATE ou MERGE.
     * Toutes les erreurs remontent en Throwable — le loop appelant les capture
     * dans $stats['errors'].
     */
    private function ingestSheet(
        array $sheetData,
        bool $dryRun,
        Sheet $sheetModel,
        SheetManager $sheetManager,
        SheetBeneficiary $benefRelation,
        SheetStipulation $stipRelation,
        User $batchUser,
        array &$stats
    ): void {
        $achatId     = $sheetData['achat_id_cockpit'];
        $label       = (string) ($sheetData['label'] ?? '');
        $nbFiliales  = count($sheetData['filiales']  ?? []);
        $nbFiliales0 = count($sheetData['filiales0'] ?? []);
        $nbContracts = count($sheetData['contracts'] ?? []);
        $nbProviders = count($sheetData['providers'] ?? []);

        // Étape 1 : lookup par achat_id_cockpit (cas nominal)
        $existing  = $sheetModel->findByAchatIdCockpit($achatId);
        $matchMode = $existing !== null ? 'achat_id' : null;

        // Étape 2 : sinon, lookup par contract_code (rattrape les fiches manuelles)
        if ($existing === null) {
            $contractCodes = [];
            foreach ($sheetData['contracts'] ?? [] as $c) {
                if (!empty($c['contract_code'])) {
                    $contractCodes[] = $c['contract_code'];
                }
            }
            if (!empty($contractCodes)) {
                $existing = $sheetModel->findByContractCodes($contractCodes);
                if ($existing !== null) {
                    $matchMode = 'contract_code';
                    $stats['matched_by_contract']++;
                }
            }
        }

        if ($existing === null) {
            // === CREATE : rien trouvé → création complète en brouillon
            error_log(sprintf(
                "[hlk-sheet-import] CREATE%s achat_id_cockpit=%s label='%s' | providers=%d contracts=%d filiales=%d filiales0=%d",
                $dryRun ? ' [dry]' : '',
                $achatId, $label, $nbProviders, $nbContracts, $nbFiliales, $nbFiliales0
            ));
            if (!$dryRun) {
                $sheetManager->add($batchUser, $sheetData);
            }
            $stats['created']++;
            return;
        }

        // === MERGE : fiche existante — union des filiales (bénéficiaires + stipulations)
        $existingId         = (int) $existing['id'];
        $existingAchatIdRaw = $existing['achat_id_cockpit'] ?? null;

        error_log(sprintf(
            "[hlk-sheet-import] MERGE%s sheet_id=%d achat_id_cockpit=%s label='%s' | via=%s | filiales à ajouter=%d",
            $dryRun ? ' [dry]' : '',
            $existingId, $achatId, $label, $matchMode, $nbFiliales
        ));

        if (!$dryRun) {
            // Backfill : si trouvée via contract_code et sans achat_id_cockpit,
            // on le pose pour matcher plus vite / plus fiablement aux prochains runs.
            if ($matchMode === 'contract_code' && empty($existingAchatIdRaw)) {
                $sheetModel->updateAchatIdCockpit($existingId, $achatId);
                $stats['backfilled']++;
                error_log("[hlk-sheet-import]   backfill achat_id_cockpit={$achatId} sur sheet_id={$existingId}");
            }

            // Merge filiales (bénéficiaires) : union avec l'existant
            foreach ($sheetData['filiales'] ?? [] as $f) {
                if (isset($f['id']) && $f['id'] !== null) {
                    $benefRelation->add($existingId, (int) $f['id']);
                    $stats['filiales_added']++;
                }
            }
            // Merge filiales0 (stipulations pour autrui) : idem
            foreach ($sheetData['filiales0'] ?? [] as $f) {
                if (isset($f['id']) && $f['id'] !== null) {
                    $stipRelation->add($existingId, (int) $f['id']);
                    $stats['filiales0_added']++;
                }
            }
        }
        $stats['updated']++;
    }

    /**
     * Utilisateur technique utilisé pour l'audit created_by/updated_by.
     * Pas de session HTTP dans un batch → User instancié à la main.
     */
    private function buildBatchUser(): User
    {
        $user = new User();
        $user->setNni(self::BATCH_USER_NNI);
        $user->setFullname(self::BATCH_USER_FULLNAME);
        return $user;
    }
}
