<?php

namespace App\Manager;

use App\Models\Sheet;
use App\Models\SyncRun;
use App\Security\User;
use App\Services\HlkEligibility;
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
 *   2. Sinon, lookup secondaire par `contract_code` (détecte les fiches
 *      créées manuellement pour ce marché)
 *   3. Si trouvée par l'un ou l'autre : **SKIP** — la spec est "création only",
 *      on ne touche jamais une fiche existante (ni merge, ni backfill).
 *   4. Si absente : création complète en brouillon via `SheetManager::add()`
 *      avec `auto_created = true`
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

    /** Nom du job pour la traçabilité dans sync_state / sync_runs. */
    public const JOB_NAME = 'import_sheets_from_hlk';

    /**
     * @param bool $dryRun Si true : aucune écriture BDD, uniquement rapport.
     *   La ligne sync_runs est quand même écrite (avec dry_run=true) pour que
     *   les simulations restent traçables dans l'historique.
     * @param string|null $signedSince Curseur `signed_date` HLK. Null = pas de filtre.
     * @param string $triggeredBy Origine du run — 'cli' | 'cron' | 'admin:<NNI>' | 'webhook'.
     *   Persisté dans sync_runs.triggered_by pour tracer qui a lancé quoi.
     * @param callable|null $onProgress Callback appelé aux étapes clés du run
     *   avec (string $stage, array $partial) — utile depuis un controller HTTP
     *   pour émettre des octets keepalive et éviter les timeouts intermédiaires
     *   (nginx / WAF EDF) sur ce traitement long (~5-10 min).
     *   $stage ∈ { 'page_fetched', 'map_done', 'ingest_progress' }
     *
     * @return array Rapport structuré (cf. docblock complet dans le corps).
     */
    public function run(
        bool $dryRun = false,
        ?string $signedSince = null,
        string $triggeredBy = 'cli',
        ?callable $onProgress = null
    ): array {
        $started = new \DateTimeImmutable();
        $t0 = microtime(true);

        $stats = [
            'dry_run'              => $dryRun,
            'signed_since'         => $signedSince,
            'pages_processed'      => 0,
            'rows_fetched'         => 0,
            'rows_eligible'        => 0,   // rows gardées après filtre "contrat_ouvert_filiales = true"
            'rows_ineligible'      => 0,   // rows droppées par le filtre (contrat_ouvert_filiales != true)
            'sheets_aggregated'    => 0,
            'created'              => 0,
            'skipped_existing'     => 0,   // fiche EHA déjà présente → aucune écriture
            'skipped_by_achat_id'  => 0,   // ↳ détail : trouvée via achat_id_cockpit
            'skipped_by_contract'  => 0,   // ↳ détail : trouvée via contract_code (fiche manuelle)
            'errors'               => [],
            'warnings'             => [],
        ];

        error_log('[hlk-sheet-import] ================================================================');
        error_log('[hlk-sheet-import] Démarrage import ' . ($dryRun ? '[DRY-RUN]' : '[REAL]') . ' | signed_since=' . ($signedSince ?? '(none)') . ' | triggered_by=' . $triggeredBy);

        // Traçabilité : insère immédiatement une ligne 'running' dans sync_runs.
        // L'admin verra le run en cours dans l'écran d'historique dès son
        // démarrage. Si l'insert échoue (BDD down), on continue quand même le
        // batch — la traçabilité n'est pas critique métier.
        $syncRun = new SyncRun();
        $runId = null;
        try {
            $runId = $syncRun->startRun(self::JOB_NAME, $triggeredBy, $dryRun, $signedSince);
            error_log("[hlk-sheet-import] sync_runs id={$runId} enregistré (status=running)");
        } catch (Throwable $e) {
            error_log("[hlk-sheet-import] ⚠ startRun failed : {$e->getMessage()} — le batch continue sans traçabilité BDD");
        }

        // IDs des fiches créées par ce run — persistés dans sync_runs.created_sheet_ids
        // pour le drill-down UI ("voir les N fiches créées ce run").
        $createdSheetIds = [];

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

                // Keepalive : émet un signal au controller HTTP (le controller
                // enverra un octet vers le browser pour maintenir la connexion).
                $this->safeProgress($onProgress, 'page_fetched', ['page' => $pageNum, 'rows_fetched' => $stats['rows_fetched']]);
            }
        } catch (Throwable $e) {
            $stats['errors'][] = [
                'level' => 'fatal_fetch',
                'message' => $e->getMessage(),
                'after_page' => $stats['pages_processed'],
            ];
            error_log("[hlk-sheet-import] ⚠ FATAL fetch : {$e->getMessage()}");
        }

        // === PHASE 1b : Filtre éligibilité "contrat ouvert aux filiales" =====
        // Règle métier : le batch ne traite QUE les contrats HLK marqués
        // `contrat_ouvert_filiales = true`. Les autres sont hors périmètre.
        //
        // Filtre row-par-row (pas achat-par-achat) : si un même achat_id_cockpit
        // a plusieurs contrats dont certains ouverts aux filiales et d'autres non,
        // on ne garde que les contrats ouverts → la fiche générée ne contient
        // que la partie éligible.
        //
        // Même règle et même helper que HlkBeneficiarySyncManager, via
        // HlkEligibility — garantit la cohérence entre les 2 features.
        $filteredRows = [];
        foreach ($allRows as $row) {
            if (HlkEligibility::isContratOpenToFiliales($row['contrat_ouvert_filiales'] ?? null)) {
                $filteredRows[] = $row;
            }
        }
        $stats['rows_eligible']   = count($filteredRows);
        $stats['rows_ineligible'] = count($allRows) - count($filteredRows);
        error_log(sprintf(
            '[hlk-sheet-import] filtre contrat_ouvert_filiales=true : %d rows gardées / %d droppées (sur %d)',
            $stats['rows_eligible'], $stats['rows_ineligible'], count($allRows)
        ));
        $allRows = $filteredRows;
        unset($filteredRows);

        // === PHASE 2 : Agrégation via mapper ===============================
        $mapper = new HlkSheetsMapper();
        try {
            $sheets = $mapper->transform($allRows);
            $stats['sheets_aggregated'] = count($sheets);
            $stats['category_usage']    = $mapper->getCategoryUsage();
            // Libération mémoire : les rows brutes ne servent plus après l'agrégation.
            // Sur ~40k rows × ~1KB, ça peut libérer 40-80MB pour la phase d'ingest
            // qui va instancier plein de models Sheet / SheetManager.
            unset($allRows);
            $this->safeProgress($onProgress, 'map_done', ['sheets_aggregated' => count($sheets)]);
        } catch (Throwable $e) {
            $stats['errors'][] = [
                'level'   => 'fatal_map',
                'message' => $e->getMessage(),
            ];
            error_log("[hlk-sheet-import] ⚠ FATAL map : {$e->getMessage()}");
            $sheets = [];
        }

        // === PHASE 3 : Ingestion fiche par fiche ============================
        // Sémantique stricte "création only" : on ne modifie AUCUNE fiche
        // existante. Toute fiche déjà présente en EHA (retrouvée par
        // achat_id_cockpit ou par contract_code) est SKIPPÉE.
        $sheetModel   = new Sheet();
        $sheetManager = new SheetManager();
        $batchUser    = $this->buildBatchUser();

        $ingestIndex = 0;
        foreach ($sheets as $sheetData) {
            $ingestIndex++;
            try {
                $this->ingestSheet(
                    $sheetData, $dryRun,
                    $sheetModel, $sheetManager, $batchUser,
                    $stats, $createdSheetIds
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

            // Keepalive : signal périodique tous les 20 sheets ingérés
            // (l'ingestion peut prendre plusieurs secondes par sheet quand il y
            // a beaucoup de contrats/providers/filiales à insérer).
            if ($ingestIndex % 20 === 0) {
                $this->safeProgress($onProgress, 'ingest_progress', [
                    'ingested' => $ingestIndex,
                    'total'    => count($sheets),
                    'created'  => $stats['created'],
                    'skipped'  => $stats['skipped_existing'],
                ]);
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
        $stats['created_sheet_ids'] = $createdSheetIds;

        // === Status final pour sync_runs =====================================
        // - 'error'   : au moins une erreur fatale (fetch ou map) → run vraiment cassé
        // - 'partial' : erreurs row-level OU pages HLK skipées → certaines rows perdues
        // - 'success' : rien de tout ça
        $hasFatal = false;
        foreach ($stats['errors'] as $err) {
            $lvl = $err['level'] ?? '';
            if (is_string($lvl) && str_starts_with($lvl, 'fatal')) {
                $hasFatal = true;
                break;
            }
        }
        $status = $hasFatal ? 'error'
                : (empty($stats['errors']) && empty($stats['warnings']) ? 'success' : 'partial');

        // Curseur pour le prochain run — la date du jour SAUF si error fatale
        // (dans ce cas on garde l'ancien curseur pour rejouer la même fenêtre).
        $nextCursor = $hasFatal ? null : date('Y-m-d');
        $stats['next_cursor']       = $nextCursor;
        $stats['status']            = $status;
        $stats['triggered_by']      = $triggeredBy;

        // Persistance sync_runs (best-effort — pas critique si ça échoue)
        if ($runId !== null) {
            try {
                $syncRun->finishRun(
                    $runId, $status, $nextCursor,
                    $stats, $stats['errors'], $stats['warnings'], $createdSheetIds
                );
                $stats['sync_run_id'] = $runId;
                error_log("[hlk-sheet-import] sync_runs id={$runId} finalisé (status={$status})");
            } catch (Throwable $e) {
                error_log("[hlk-sheet-import] ⚠ finishRun failed run_id={$runId} : {$e->getMessage()}");
            }
        }

        error_log(sprintf(
            '[hlk-sheet-import] terminé %s [%s] en %dms | pages=%d rows=%d eligible=%d (ineligible=%d) sheets=%d | created=%d skipped_existing=%d (via achat_id=%d, via contract=%d) | errors=%d warnings=%d',
            $dryRun ? '[DRY-RUN]' : '[REAL]',
            strtoupper($status),
            $stats['duration_ms'],
            $stats['pages_processed'], $stats['rows_fetched'],
            $stats['rows_eligible'], $stats['rows_ineligible'],
            $stats['sheets_aggregated'],
            $stats['created'], $stats['skipped_existing'],
            $stats['skipped_by_achat_id'], $stats['skipped_by_contract'],
            count($stats['errors']), count($stats['warnings'])
        ));
        error_log('[hlk-sheet-import] ================================================================');

        return $stats;
    }

    /**
     * Ingère UNE fiche (issue du mapper) : CREATE si absente en EHA, SKIP sinon.
     *
     * Contrat métier strict "création only" : aucune fiche existante n'est
     * modifiée (pas de merge des bénéficiaires, pas de backfill). Le lookup
     * secondaire par contract_code sert UNIQUEMENT à détecter la duplication —
     * on ne touche pas à la fiche trouvée.
     *
     * Toutes les erreurs remontent en Throwable — le loop appelant les capture
     * dans $stats['errors'].
     */
    private function ingestSheet(
        array $sheetData,
        bool $dryRun,
        Sheet $sheetModel,
        SheetManager $sheetManager,
        User $batchUser,
        array &$stats,
        array &$createdSheetIds
    ): void {
        $achatId     = $sheetData['achat_id_cockpit'];
        $label       = (string) ($sheetData['label'] ?? '');
        $nbContracts = count($sheetData['contracts'] ?? []);
        $nbProviders = count($sheetData['providers'] ?? []);
        $nbFiliales  = count($sheetData['filiales']  ?? []);
        $nbFiliales0 = count($sheetData['filiales0'] ?? []);

        // Étape 1 : lookup par achat_id_cockpit (cas nominal — batch précédent)
        $existing = $sheetModel->findByAchatIdCockpit($achatId);
        if ($existing !== null) {
            $stats['skipped_existing']++;
            $stats['skipped_by_achat_id']++;
            error_log(sprintf(
                "[hlk-sheet-import] SKIP%s achat_id_cockpit=%s (déjà en EHA sheet_id=%d, via achat_id)",
                $dryRun ? ' [dry]' : '',
                $achatId, (int) $existing['id']
            ));
            return;
        }

        // Étape 2 : lookup secondaire par contract_code (détecte les fiches
        // créées manuellement pour ce marché — on ne les touche pas non plus).
        $contractCodes = [];
        foreach ($sheetData['contracts'] ?? [] as $c) {
            if (!empty($c['contract_code'])) {
                $contractCodes[] = $c['contract_code'];
            }
        }
        if (!empty($contractCodes)) {
            $existing = $sheetModel->findByContractCodes($contractCodes);
            if ($existing !== null) {
                $stats['skipped_existing']++;
                $stats['skipped_by_contract']++;
                error_log(sprintf(
                    "[hlk-sheet-import] SKIP%s achat_id_cockpit=%s (déjà en EHA sheet_id=%d, via contract_code — fiche manuelle)",
                    $dryRun ? ' [dry]' : '',
                    $achatId, (int) $existing['id']
                ));
                return;
            }
        }

        // === CREATE : rien trouvé → création complète en brouillon
        error_log(sprintf(
            "[hlk-sheet-import] CREATE%s achat_id_cockpit=%s label='%s' | providers=%d contracts=%d filiales=%d filiales0=%d",
            $dryRun ? ' [dry]' : '',
            $achatId, $label, $nbProviders, $nbContracts, $nbFiliales, $nbFiliales0
        ));
        if (!$dryRun) {
            // SheetManager::add() renvoie un JSON string {id, data} — on décode
            // pour récupérer l'id de la fiche créée, à persister dans
            // sync_runs.created_sheet_ids pour le drill-down UI.
            $addResult = $sheetManager->add($batchUser, $sheetData);
            $decoded   = is_string($addResult) ? json_decode($addResult, true) : null;
            $newId     = is_array($decoded) ? (int) ($decoded['id'] ?? 0) : 0;
            if ($newId > 0) {
                $createdSheetIds[] = $newId;
            } else {
                error_log("[hlk-sheet-import] ⚠ CREATE achat_id_cockpit={$achatId} : impossible de récupérer l'id (SheetManager::add retour inattendu)");
            }
        }
        $stats['created']++;
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

    /**
     * Invoque proprement le callback onProgress. Isolé pour :
     *   1. Éviter un check `!== null` répété partout dans le code
     *   2. Protéger le batch d'une exception dans le callback (le controller
     *      appelant peut throw si le client HTTP a fermé la connexion — on ne
     *      veut pas perdre les rows/fiches restantes pour autant)
     */
    private function safeProgress(?callable $onProgress, string $stage, array $partial): void
    {
        if ($onProgress === null) return;
        try {
            $onProgress($stage, $partial);
        } catch (Throwable $e) {
            error_log("[hlk-sheet-import] ⚠ onProgress callback threw ({$stage}) : " . $e->getMessage());
        }
    }
}
