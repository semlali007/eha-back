<?php

declare(strict_types=1);

namespace App\Command\Command;

use App\Manager\SheetManager;
use App\Models\Sheet;
use App\Models\SheetBeneficiary;
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

        $syncState = new SyncState();
        $state     = $syncState->getByJobName(self::JOB_NAME);

        // Détermination du curseur : override CLI > dernier curseur BDD > fallback
        $since = $overrideSince ?: ($state['last_cursor'] ?? self::DEFAULT_FALLBACK_SINCE);
        $io->section("Curseur signed_date >= {$since}" . ($dryRun ? '  [DRY-RUN]' : ''));

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

            // 3) Ingestion
            $sheetModel   = new Sheet();
            $sheetManager = new SheetManager();
            $benefRelation = new SheetBeneficiary();
            $batchUser    = $this->buildBatchUser();

            $stats['type_mismatch'] = 0; // fiches trouvées avec un netachat_type ≠ 'hlk'

            foreach ($sheets as $sheetData) {
                try {
                    $netachatId   = $sheetData['netachat_id'];
                    $netachatType = $sheetData['netachat_type'];
                    // Lookup souple : on cherche par netachat_id seul (peu importe le type
                    // stocké) pour éviter de créer un doublon si la fiche a été importée
                    // par un ancien mécanisme (netachat_type NULL ou legacy).
                    $existing = $sheetModel->findByNetachatId($netachatId);

                    if ($existing === null) {
                        // Création complète en brouillon
                        if ($dryRun) {
                            $io->text("  [dry] CREATE fiche netachat_id={$netachatId} label='{$sheetData['label']}'");
                        } else {
                            $sheetManager->add($batchUser, $sheetData);
                        }
                        $stats['created']++;
                    } else {
                        $existingId       = (int) $existing['id'];
                        $existingTypeRaw  = $existing['netachat_type'] ?? null;

                        // Le netachat_type est purement informatif : on le pose sur les nouvelles
                        // fiches à titre de traçabilité, mais il n'entre PAS dans la logique
                        // d'idempotence (on lookup par netachat_id seul). Si le type stocké
                        // diffère de 'hlk', c'est probablement une fiche importée par un
                        // ancien mécanisme — on la traite normalement et on note juste dans
                        // les stats pour visibilité.
                        if ($existingTypeRaw !== $netachatType) {
                            $stats['type_mismatch']++;
                            error_log(sprintf(
                                '[ImportSheetsFromHlk] info : sheet_id=%d netachat_id=%s a un netachat_type=%s (vs %s côté HLK). Merge filiales appliqué quand même.',
                                $existingId,
                                (string) $netachatId,
                                var_export($existingTypeRaw, true),
                                $netachatType
                            ));
                        }

                        // Update partiel : uniquement les bénéficiaires (union avec l'existant)
                        if ($dryRun) {
                            $io->text("  [dry] MERGE filiales sheet_id={$existingId} netachat_id={$netachatId} (existing_type=" . var_export($existingTypeRaw, true) . ')');
                        } else {
                            foreach ($sheetData['filiales'] as $f) {
                                if (isset($f['code']) && $f['code'] !== null) {
                                    $benefRelation->add($existingId, (int) $f['code']);
                                }
                            }
                        }
                        $stats['updated']++;
                    }
                } catch (Throwable $e) {
                    $stats['errors']++;
                    error_log('[ImportSheetsFromHlk] erreur fiche netachat_id=' . ($sheetData['netachat_id'] ?? '?') . ' : ' . $e->getMessage());
                }
            }

            // 4) Nouveau curseur : aujourd'hui (les prochaines syncs reprendront de là)
            $newCursor = date('Y-m-d');

            $status = $stats['errors'] === 0 ? 'success' : 'partial';
            if (!$dryRun) {
                $syncState->upsert(self::JOB_NAME, $newCursor, $status, null, $stats);
            }

            $io->success('Import terminé');
            $io->table(
                ['fetched_rows', 'sheets', 'created', 'updated', 'errors', 'nouveau curseur'],
                [[$stats['fetched_rows'], $stats['sheets'], $stats['created'], $stats['updated'], $stats['errors'], $newCursor]]
            );
            return Command::SUCCESS;

        } catch (Throwable $e) {
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
