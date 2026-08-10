<?php

declare(strict_types=1);

namespace App\Command\Command;

use App\Manager\HlkSheetImportManager;
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
 * Coquille CLI autour de HlkSheetImportManager : délégation intégrale, y
 * compris pour le curseur `sync_state` — lu et persisté par le manager
 * lui-même (identique au déclenchement via l'endpoint admin HTTP,
 * HlkTriggerController, pour rester "iso batch" quel que soit l'appelant).
 *
 * L'historique complet du run (stats, errors, warnings, IDs fiches créées)
 * est écrit dans sync_runs par le manager lui-même — la CLI n'a rien à faire
 * pour ça, l'écran admin le lira directement.
 *
 * Usage :
 *   php bin/console app:import-sheets:hlk                        → sync incrémentale (curseur auto)
 *   php bin/console app:import-sheets:hlk --since=YYYY-MM-DD     → force le curseur
 *   php bin/console app:import-sheets:hlk --dry-run              → simulation
 *   php bin/console app:import-sheets:hlk --triggered-by=cron    → tag l'origine
 */
class ImportSheetsFromHlk extends Command
{
    protected static $defaultName = 'app:import-sheets:hlk';

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
        // Batch potentiellement long + volumineux — même config que l'endpoint
        // admin (HlkTriggerController) pour un comportement cohérent CLI/HTTP.
        set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        $io = new style($input, $output);
        $io->title('Import fiches marché HLK');

        $dryRun        = (bool) $input->getOption('dry-run');
        $overrideSince = $input->getOption('since');
        $triggeredBy   = (string) $input->getOption('triggered-by');

        // null → HlkSheetImportManager résout le curseur depuis sync_state.
        $since = $overrideSince ?: null;

        $io->section(
            ($since !== null ? "Curseur signed_date >= {$since} (override)" : 'Curseur automatique (sync_state)')
            . ($dryRun ? '  [DRY-RUN]' : '') . " | triggered_by={$triggeredBy}"
        );
        error_log('[import-hlk] ================================================================');
        error_log('[import-hlk] Batch start | since=' . ($since ?? '(auto, résolu par le manager)') . ' | mode=' . ($dryRun ? 'DRY-RUN' : 'REAL') . " | triggered_by={$triggeredBy}");
        error_log('[import-hlk] ================================================================');

        try {
            // === Délégation intégrale au manager (curseur inclus) ==========
            $manager = new HlkSheetImportManager();
            $stats   = $manager->run($dryRun, $since, $triggeredBy);

            $status    = $stats['status']       ?? 'partial';
            $newCursor = $stats['next_cursor']  ?? $stats['signed_since'] ?? (string) $since;

            $this->logFinalSummary($stats, (string) $newCursor, $status);
            $this->printCliSummary($io, $stats, (string) $newCursor);

            return Command::SUCCESS;

        } catch (Throwable $e) {
            error_log('[import-hlk] BATCH EN ÉCHEC : ' . $e->getMessage());
            $io->error('Batch en échec : ' . $e->getMessage());
            return Command::FAILURE;
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
