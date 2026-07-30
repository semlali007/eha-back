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
 * Coquille CLI autour de HlkSheetImportManager :
 *   1. Lit le curseur `signed_date` depuis sync_state (last_cursor)
 *   2. Appelle HlkSheetImportManager::run($dryRun, $signedSince, $triggeredBy)
 *   3. Persiste le nouveau curseur dans sync_state
 *
 * L'historique complet du run (stats, errors, warnings, IDs fiches créées)
 * est écrit dans sync_runs par le manager lui-même — la CLI n'a rien à faire
 * pour ça, l'écran admin le lira directement.
 *
 * Usage :
 *   php bin/console app:import-sheets:hlk                        → sync incrémentale
 *   php bin/console app:import-sheets:hlk --since=YYYY-MM-DD     → force le curseur
 *   php bin/console app:import-sheets:hlk --dry-run              → simulation
 *   php bin/console app:import-sheets:hlk --triggered-by=cron    → tag l'origine
 */
class ImportSheetsFromHlk extends Command
{
    protected static $defaultName = 'app:import-sheets:hlk';

    private const JOB_NAME               = HlkSheetImportManager::JOB_NAME;
    private const DEFAULT_FALLBACK_SINCE = '2000-01-01';

    protected function configure(): void
    {
        $this
            ->setDescription('Importe les fiches marché depuis HLK et les crée en brouillon')
            ->addOption('since',        null, InputOption::VALUE_OPTIONAL, 'Date de départ YYYY-MM-DD (override du curseur)')
            ->addOption('dry-run',      null, InputOption::VALUE_NONE,      'Simule sans écrire en BDD')
            ->addOption('triggered-by', null, InputOption::VALUE_OPTIONAL, 'Origine du run (cli, cron, webhook, admin:NNI)', 'cli');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new style($input, $output);
        $io->title('Import fiches marché HLK');

        $dryRun        = (bool) $input->getOption('dry-run');
        $overrideSince = $input->getOption('since');
        $triggeredBy   = (string) $input->getOption('triggered-by');

        // === Curseur : override CLI > dernier curseur BDD > fallback très ancien
        $syncState = new SyncState();
        $state     = $syncState->getByJobName(self::JOB_NAME);
        $since     = $overrideSince ?: ($state['last_cursor'] ?? self::DEFAULT_FALLBACK_SINCE);

        $io->section("Curseur signed_date >= {$since}" . ($dryRun ? '  [DRY-RUN]' : '') . " | triggered_by={$triggeredBy}");
        $this->logStartHeader($since, $dryRun, $triggeredBy, $state);

        // Curseur "à persister" par défaut = celui d'entrée. Le manager peut
        // renvoyer un next_cursor différent (aujourd'hui) si le run a avancé.
        $newCursor = $since;

        try {
            // === Délégation intégrale au manager ==========================
            $manager = new HlkSheetImportManager();
            $stats   = $manager->run($dryRun, $since, $triggeredBy);

            // Le manager a déjà calculé status et next_cursor (partagés avec sync_runs)
            $status    = $stats['status']      ?? 'partial';
            $newCursor = $stats['next_cursor'] ?? $since;

            // Persistance dans sync_state (curseur du prochain run) — pas en dry_run
            if (!$dryRun && $newCursor !== null) {
                $syncState->upsert(self::JOB_NAME, $newCursor, $status, null, $stats);
            }

            $this->logFinalSummary($stats, $newCursor, $status);
            $this->printCliSummary($io, $stats, $newCursor);

            return Command::SUCCESS;

        } catch (Throwable $e) {
            error_log('[import-hlk] BATCH EN ÉCHEC : ' . $e->getMessage());
            $io->error('Batch en échec : ' . $e->getMessage());
            if (!$dryRun) {
                $syncState->upsert(self::JOB_NAME, $newCursor, 'error', $e->getMessage(), []);
            }
            return Command::FAILURE;
        }
    }

    private function logStartHeader(string $since, bool $dryRun, string $triggeredBy, ?array $state): void
    {
        error_log('[import-hlk] ================================================================');
        error_log("[import-hlk] Batch start | since={$since} | mode=" . ($dryRun ? 'DRY-RUN' : 'REAL') . " | triggered_by={$triggeredBy} | job=" . self::JOB_NAME);
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

    private function logFinalSummary(array $stats, string $newCursor, string $status): void
    {
        error_log('[import-hlk] ================================================================');
        error_log(sprintf(
            '[import-hlk] BATCH TERMINÉ (%s) en %dms | created=%d skipped_existing=%d (achat_id=%d, contract=%d) errors=%d | sync_run_id=%s | new_cursor=%s',
            strtoupper($status),
            $stats['duration_ms']         ?? 0,
            $stats['created']             ?? 0,
            $stats['skipped_existing']    ?? 0,
            $stats['skipped_by_achat_id'] ?? 0,
            $stats['skipped_by_contract'] ?? 0,
            count($stats['errors']        ?? []),
            $stats['sync_run_id']         ?? 'n/a',
            $newCursor
        ));
        error_log('[import-hlk] ================================================================');
    }

    private function printCliSummary(style $io, array $stats, string $newCursor): void
    {
        $io->success('Import terminé — run_id=' . ($stats['sync_run_id'] ?? 'n/a'));
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
