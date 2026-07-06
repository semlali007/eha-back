<?php

declare(strict_types=1);

namespace App\Command\Command;

use App\Manager\SheetManager;
use App\Models\Sheet;
use App\Models\SheetBeneficiary;
use App\Models\SheetStipulation;
use App\Models\SyncState;
use App\Security\User;
use App\Services\HlkSheetsMapper;
use App\Services\HlkSheetsService;
use Symfony\Component\Console\{
    Command\Command,
    Input\InputInterface,
    Input\InputOption,
    Output\OutputInterface,
    Style\SymfonyStyle as style
};
use Throwable;

/**
 * Batch d'import automatique des fiches marché depuis HLK.
 *
 * Chaque exécution :
 *   1. Lit le curseur (last_cursor = signed_date de la dernière sync réussie)
 *   2. Appelle HLK avec ce curseur pour ne récupérer que les nouveaux marchés
 *   3. Agrège le payload (produit cartésien → 1 fiche par achat_id_cockpit)
 *   4. Pour chaque fiche :
 *       - Si absente (netachat_id/type) → création en brouillon via SheetManager::add()
 *       - Si présente → mise à jour partielle : uniquement les bénéficiaires (union)
 *   5. Persiste le nouveau curseur et les stats
 *
 * Usage :
 *   php bin/console app:import-sheets:hlk                 → sync incrémentale
 *   php bin/console app:import-sheets:hlk --since=YYYY-MM-DD  → force une date de départ
 *   php bin/console app:import-sheets:hlk --dry-run       → n'écrit rien en BDD, log ce qui serait fait
 */
class ImportSheetsFromHlk extends Command
{
    protected static $defaultName = 'app:import-sheets:hlk';

    private const JOB_NAME       = 'import_sheets_from_hlk';
    private const DEFAULT_FALLBACK_SINCE = '2000-01-01';
    private const BATCH_USER_NNI = 'HLK_BATCH';

    protected function configure(): void
    {
        $this
            ->setDescription('Importe les fiches marché depuis HLK et les crée en brouillon')
            ->addOption('since',   null, InputOption::VALUE_OPTIONAL, 'Date de départ YYYY-MM-DD (override du curseur)')
            ->addOption('dry-run', null, InputOption::VALUE_NONE,      'Simule sans écrire en BDD');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new style($input, $output);
        $io->title('Import fiches marché HLK');

        $dryRun = (bool) $input->getOption('dry-run');
        $overrideSince = $input->getOption('since');
        $tGlobal = microtime(true);

        $syncState = new SyncState();
        $state     = $syncState->getByJobName(self::JOB_NAME);

        // Détermination du curseur : override CLI > dernier curseur BDD > fallback
        $since = $overrideSince ?: ($state['last_cursor'] ?? self::DEFAULT_FALLBACK_SINCE);
        $io->section("Curseur signed_date >= {$since}" . ($dryRun ? '  [DRY-RUN]' : ''));

        error_log("[import-hlk] ================================================================");
        error_log("[import-hlk] Batch start | since={$since} | mode=" . ($dryRun ? 'DRY-RUN' : 'REAL') . " | job=" . self::JOB_NAME);
        error_log("[import-hlk] ================================================================");
        if ($state) {
            error_log(sprintf(
                "[import-hlk] État précédent : last_run=%s last_success=%s last_cursor=%s last_status=%s",
                $state['last_run_at']     ?? 'n/a',
                $state['last_success_at'] ?? 'n/a',
                $state['last_cursor']     ?? 'n/a',
                $state['last_status']     ?? 'n/a'
            ));
        } else {
            error_log("[import-hlk] Aucun état précédent (première exécution)");
        }

        $stats = ['fetched_rows' => 0, 'sheets' => 0, 'created' => 0, 'updated' => 0, 'errors' => 0];
        $newCursor = $since;

        try {
            // 1) Fetch tous les payloads HLK
            $service = new HlkSheetsService();
            $rows    = $service->fetchAllSince($since);
            $stats['fetched_rows'] = count($rows);
            $io->text("HLK a renvoyé {$stats['fetched_rows']} lignes brutes (produit cartésien)");

            // 2) Agrégation → fiches
            $mapper = new HlkSheetsMapper();
            $sheets = $mapper->transform($rows);
            $stats['sheets'] = count($sheets);
            $io->text("→ {$stats['sheets']} fiches uniques après agrégation par achat_id_cockpit");

            // On persiste dans sync_state le résumé d'usage des catégories, pour
            // pouvoir revenir dessus a posteriori (analyse / requête SQL).
            $stats['category_usage'] = $mapper->getCategoryUsage();

            // 3) Ingestion
            error_log("[import-hlk:ingest] ===== Début ingestion de {$stats['sheets']} fiches (mode=" . ($dryRun ? 'DRY-RUN' : 'REAL') . ') =====');
            $sheetModel   = new Sheet();
            $sheetManager = new SheetManager();
            $benefRelation      = new SheetBeneficiary();
            $stipulationRelation = new SheetStipulation();
            $batchUser    = $this->buildBatchUser();

            $stats['filiales_added']       = 0; // total de liens (sheet, filiale) ajoutés (type=beneficiary)
            $stats['filiales0_added']      = 0; // idem pour les stipulations (type=stipulation)
            $stats['matched_by_contract']  = 0; // fiches retrouvées via contract_code (pas via achat_id)
            $stats['backfilled']           = 0; // fiches sur lesquelles on a rempli achat_id_cockpit

            foreach ($sheets as $sheetData) {
                try {
                    $achatId      = $sheetData['achat_id_cockpit'];
                    $label        = (string) ($sheetData['label'] ?? '');
                    $nbFiliales   = count($sheetData['filiales']  ?? []);
                    $nbFiliales0  = count($sheetData['filiales0'] ?? []);
                    $nbContracts  = count($sheetData['contracts'] ?? []);
                    $nbProviders  = count($sheetData['providers'] ?? []);

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
                        // Étape 3 : rien trouvé → création complète en brouillon
                        error_log(sprintf(
                            "[import-hlk:ingest] CREATE%s achat_id_cockpit=%s label='%s' | providers=%d contracts=%d filiales=%d filiales0=%d",
                            $dryRun ? ' [dry]' : '',
                            $achatId, $label, $nbProviders, $nbContracts, $nbFiliales, $nbFiliales0
                        ));
                        if ($dryRun) {
                            $io->text("  [dry] CREATE fiche achat_id_cockpit={$achatId} label='{$label}' ({$nbFiliales} filiales, {$nbFiliales0} stipulations, {$nbContracts} contrats)");
                        } else {
                            $sheetManager->add($batchUser, $sheetData);
                        }
                        $stats['created']++;
                    } else {
                        $existingId          = (int) $existing['id'];
                        $existingAchatIdRaw  = $existing['achat_id_cockpit'] ?? null;

                        error_log(sprintf(
                            "[import-hlk:ingest] MERGE%s sheet_id=%d achat_id_cockpit=%s label='%s' | via=%s | filiales à ajouter=%d",
                            $dryRun ? ' [dry]' : '',
                            $existingId, $achatId, $label, $matchMode, $nbFiliales
                        ));

                        if ($dryRun) {
                            $io->text("  [dry] MERGE filiales sheet_id={$existingId} ({$nbFiliales} filiales à unir) [via {$matchMode}]");
                        } else {
                            // Backfill : si on a trouvé la fiche via contract_code et qu'elle
                            // n'avait pas d'achat_id_cockpit, on le pose pour matcher plus
                            // vite / plus fiablement aux prochains runs.
                            if ($matchMode === 'contract_code' && empty($existingAchatIdRaw)) {
                                $sheetModel->updateAchatIdCockpit($existingId, $achatId);
                                $stats['backfilled']++;
                                error_log("[import-hlk:ingest]   backfill achat_id_cockpit={$achatId} sur sheet_id={$existingId}");
                            }

                            // Merge des filiales (bénéficiaires) : union avec l'existant
                            foreach ($sheetData['filiales'] as $f) {
                                if (isset($f['id']) && $f['id'] !== null) {
                                    $benefRelation->add($existingId, (int) $f['id']);
                                    $stats['filiales_added']++;
                                }
                            }
                            // Merge des filiales0 (stipulations pour autrui) : idem
                            foreach ($sheetData['filiales0'] ?? [] as $f) {
                                if (isset($f['id']) && $f['id'] !== null) {
                                    $stipulationRelation->add($existingId, (int) $f['id']);
                                    $stats['filiales0_added']++;
                                }
                            }
                        }
                        $stats['updated']++;
                    }
                } catch (Throwable $e) {
                    $stats['errors']++;
                    error_log('[import-hlk:ingest] ⚠ erreur fiche achat_id_cockpit=' . ($sheetData['achat_id_cockpit'] ?? '?') . ' : ' . $e->getMessage());
                }
            }

            // 4) Nouveau curseur : aujourd'hui (les prochaines syncs reprendront de là)
            $newCursor = date('Y-m-d');

            $status = $stats['errors'] === 0 ? 'success' : 'partial';
            if (!$dryRun) {
                $syncState->upsert(self::JOB_NAME, $newCursor, $status, null, $stats);
            }

            $totalMs = round((microtime(true) - $tGlobal) * 1000);
            error_log("[import-hlk] ================================================================");
            error_log(sprintf(
                "[import-hlk] BATCH TERMINÉ (%s) en %dms | created=%d updated=%d (dont via_contract=%d, backfilled=%d) errors=%d filiales_added=%d filiales0_added=%d | new_cursor=%s",
                strtoupper($status),
                $totalMs,
                $stats['created'], $stats['updated'],
                $stats['matched_by_contract'], $stats['backfilled'],
                $stats['errors'],
                $stats['filiales_added'], $stats['filiales0_added'],
                $newCursor
            ));
            error_log("[import-hlk] ================================================================");

            $io->success('Import terminé');
            $io->table(
                ['fetched_rows', 'sheets', 'created', 'updated', 'via_contract', 'backfilled', 'errors', 'filiales_added', 'filiales0_added', 'nouveau curseur'],
                [[
                    $stats['fetched_rows'], $stats['sheets'], $stats['created'], $stats['updated'],
                    $stats['matched_by_contract'], $stats['backfilled'],
                    $stats['errors'], $stats['filiales_added'], $stats['filiales0_added'], $newCursor
                ]]
            );
            return Command::SUCCESS;

        } catch (Throwable $e) {
            $totalMs = round((microtime(true) - $tGlobal) * 1000);
            error_log("[import-hlk] ================================================================");
            error_log("[import-hlk] BATCH EN ÉCHEC après {$totalMs}ms : " . $e->getMessage());
            error_log("[import-hlk] Stats partielles : created={$stats['created']} updated={$stats['updated']} errors={$stats['errors']}");
            error_log("[import-hlk] ================================================================");
            $io->error('Batch en échec : ' . $e->getMessage());
            if (!$dryRun) {
                $syncState->upsert(self::JOB_NAME, $newCursor, 'error', $e->getMessage(), $stats);
            }
            return Command::FAILURE;
        }
    }

    /**
     * Utilisateur technique utilisé pour l'audit (created_by, updated_by).
     * Pas de session HTTP dans un batch → on instancie un User "à la main".
     */
    private function buildBatchUser(): User
    {
        $user = new User();
        $user->setNni(self::BATCH_USER_NNI);
        $user->setFullname('HLK Batch Import');
        return $user;
    }
}
