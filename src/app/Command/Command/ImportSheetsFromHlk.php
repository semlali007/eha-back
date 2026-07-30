<?php

declare(strict_types=1);

namespace App\Command\Command;

use App\Manager\HlkSheetImportManager;
use App\Models\SyncState;
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
 * Coquille CLI autour de HlkSheetImportManager. Rôle strict :
 *   1. Lit le curseur `signed_date` depuis sync_state (last_cursor)
 *   2. Appelle HlkSheetImportManager::run($dryRun, $signedSince)
 *   3. Persiste le nouveau curseur (aujourd'hui) et les stats dans sync_state
 *
 * Toute la logique métier (fetch + agrégation + upsert) est dans le manager,
 * pour permettre à d'autres callers (endpoint admin, tests) d'appeler la même
 * chose sans re-passer par la CLI.
 *
 * Usage :
 *   php bin/console app:import-sheets:hlk                     → sync incrémentale
 *   php bin/console app:import-sheets:hlk --since=YYYY-MM-DD  → force le curseur
 *   php bin/console app:import-sheets:hlk --dry-run           → simulation, aucune écriture BDD
 */
class ImportSheetsFromHlk extends Command
{
    protected static $defaultName = 'app:import-sheets:hlk';

    private const JOB_NAME               = 'import_sheets_from_hlk';
    private const DEFAULT_FALLBACK_SINCE = '2000-01-01';

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

        $dryRun        = (bool) $input->getOption('dry-run');
        $overrideSince = $input->getOption('since');
        $tGlobal       = microtime(true);

        // === Curseur : override CLI > dernier curseur BDD > fallback très ancien
        $syncState = new SyncState();
        $state     = $syncState->getByJobName(self::JOB_NAME);
        $since     = $overrideSince ?: ($state['last_cursor'] ?? self::DEFAULT_FALLBACK_SINCE);

        $io->section("Curseur signed_date >= {$since}" . ($dryRun ? '  [DRY-RUN]' : ''));
        $this->logStartHeader($since, $dryRun, $state);

        // Curseur "à persister" par défaut = celui d'entrée. En cas de succès ou
        // partiel on avance à aujourd'hui pour que les prochains runs continuent.
        $newCursor = $since;

        try {
            // === Délégation intégrale au manager ==========================
            $manager = new HlkSheetImportManager();
            $stats   = $manager->run($dryRun, $since);

            // Curseur nouveau : aujourd'hui (les prochaines syncs reprendront de là)
            $newCursor = date('Y-m-d');

            // Status pour sync_state : success si aucune erreur, partial sinon
            $hasFatal = false;
            foreach ($stats['errors'] as $err) {
                if (isset($err['level']) && str_starts_with((string) $err['level'], 'fatal')) {
                    $hasFatal = true;
                    break;
                }
            }
            $status = empty($stats['errors']) ? 'success' : ($hasFatal ? 'partial' : 'partial');

            if (!$dryRun) {
                $syncState->upsert(self::JOB_NAME, $newCursor, $status, null, $stats);
            }

            $this->logFinalSummary($stats, $newCursor, $tGlobal, $status);
            $this->printCliSummary($io, $stats, $newCursor);

            return Command::SUCCESS;

        } catch (Throwable $e) {
            $totalMs = round((microtime(true) - $tGlobal) * 1000);
            error_log("[import-hlk] BATCH EN ÉCHEC après {$totalMs}ms : " . $e->getMessage());
            $io->error('Batch en échec : ' . $e->getMessage());
            if (!$dryRun) {
                $syncState->upsert(self::JOB_NAME, $newCursor, 'error', $e->getMessage(), []);
            }
            return Command::FAILURE;
        }
    }

    private function logStartHeader(string $since, bool $dryRun, ?array $state): void
    {
        error_log('[import-hlk] ================================================================');
        error_log("[import-hlk] Batch start | since={$since} | mode=" . ($dryRun ? 'DRY-RUN' : 'REAL') . ' | job=' . self::JOB_NAME);
        error_log('[import-hlk] ================================================================');
        if ($state) {
            error_log(sprintf(
                '[import-hlk] État précédent : last_run=%s last_success=%s last_cursor=%s last_status=%s',
                $state['last_run_at']     ?? 'n/a',
                $state['last_success_at'] ?? 'n/a',
                $state['last_cursor']     ?? 'n/a',
                $state['last_status']     ?? 'n/a'
            ));
        } else {
            error_log('[import-hlk] Aucun état précédent (première exécution)');
        }
    }

    private function logFinalSummary(array $stats, string $newCursor, float $tGlobal, string $status): void
    {
        $totalMs = round((microtime(true) - $tGlobal) * 1000);
        error_log('[import-hlk] ================================================================');
        error_log(sprintf(
            '[import-hlk] BATCH TERMINÉ (%s) en %dms | created=%d skipped_existing=%d (achat_id=%d, contract=%d) errors=%d | new_cursor=%s',
            strtoupper($status),
            $totalMs,
            $stats['created']             ?? 0,
            $stats['skipped_existing']    ?? 0,
            $stats['skipped_by_achat_id'] ?? 0,
            $stats['skipped_by_contract'] ?? 0,
            count($stats['errors']        ?? []),
            $newCursor
        ));
        error_log('[import-hlk] ================================================================');
    }

    private function printCliSummary(style $io, array $stats, string $newCursor): void
    {
        $io->success('Import terminé');
        $io->table(
            ['rows', 'eligible', 'sheets', 'created', 'skipped', 'via_achat_id', 'via_contract', 'errors', 'nouveau curseur'],
            [[
                $stats['rows_fetched']        ?? 0,
                sprintf('%d/%d', $stats['rows_eligible'] ?? 0, ($stats['rows_eligible'] ?? 0) + ($stats['rows_ineligible'] ?? 0)),
                $stats['sheets_aggregated']   ?? 0,
                $stats['created']             ?? 0,
                $stats['skipped_existing']    ?? 0,
                $stats['skipped_by_achat_id'] ?? 0,
                $stats['skipped_by_contract'] ?? 0,
                count($stats['errors']        ?? []),
                $newCursor,
            ]]
        );
    }
}
