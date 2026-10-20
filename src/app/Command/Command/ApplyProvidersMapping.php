<?php

declare(strict_types=1);

namespace App\Command\Command;

use Core\Database\DBConnection;
use PDO;
use RuntimeException;
use Symfony\Component\Console\{
    Command\Command,
    Input\InputArgument,
    Input\InputInterface,
    Input\InputOption,
    Output\OutputInterface,
    Style\SymfonyStyle
};

/**
 * Phase 2c — Applique les UPDATE proposés par Phase 2a, après revue.
 *
 * Lit 01_to_update.csv (1 ligne par provider_code) et exécute un UPDATE
 * par target dans une transaction unique.
 *
 * Règles d'écriture :
 *   - On ne touche pas une colonne BDD si la valeur CSV est vide (préserve
 *     les valeurs existantes)
 *   - Une ligne sans aucune valeur à mettre à jour est ignorée
 *   - --dry-run = ROLLBACK final (rien n'est commité)
 *
 * Usage :
 *   bin/console app:providers:apply-mapping <input-dir> [--dry-run]
 */
class ApplyProvidersMapping extends Command
{
    protected static $defaultName = 'app:providers:apply-mapping';

    private const FILE_TO_UPDATE = '01_to_update.csv';
    private const FILE_LOG = '02_apply_log.txt';

    private const FISCAL_FIELDS = ['siren', 'numero_tva', 'code_naf'];

    private PDO $pdo;
    private string $schemaPrefix;

    protected function configure(): void
    {
        $this
            ->setDescription('Phase 2c — Applique les UPDATE proposés par Phase 2a. Supporte --dry-run.')
            ->addArgument(
                'input-dir',
                InputArgument::REQUIRED,
                'Répertoire contenant 01_to_update.csv (sortie de Phase 2a)'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Simule sans rien commiter (rollback final de la transaction)'
            )
            ->addOption(
                'delimiter',
                'd',
                InputOption::VALUE_REQUIRED,
                'Séparateur du CSV à lire',
                ';'
            )
            ->addOption(
                'file',
                'f',
                InputOption::VALUE_REQUIRED,
                'Nom du fichier à lire dans <input-dir> (défaut : 01_to_update.csv). Permet de cibler un fichier alternatif comme 04_to_update_from_diagnostic.csv.',
                self::FILE_TO_UPDATE
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $db = new DBConnection();
        $this->pdo = $db->getPDO();
        $this->schemaPrefix = $db->getSchema();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Phase 2c — Application des UPDATE');

        $dir = rtrim((string) $input->getArgument('input-dir'), DIRECTORY_SEPARATOR);
        $dryRun = (bool) $input->getOption('dry-run');
        $delimiter = (string) $input->getOption('delimiter');
        $fileName = (string) $input->getOption('file');

        if (!is_dir($dir)) {
            $io->error(sprintf('Répertoire introuvable : %s', $dir));
            return Command::FAILURE;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $fileName;
        if (!is_readable($path)) {
            $io->error(sprintf('Fichier requis introuvable : %s', $path));
            return Command::FAILURE;
        }

        if ($dryRun) {
            $io->note('Mode --dry-run : ROLLBACK final, aucune modification BDD persistée.');
        } else {
            $io->warning('Mode RÉEL : COMMIT en fin de transaction. Ctrl+C avant la fin = annulation safe.');
        }

        // 1. Lecture du CSV
        $io->section(sprintf('1. Lecture de %s', $fileName));
        $rows = $this->readToUpdate($path, $delimiter);
        $io->writeln(sprintf('  %d lignes lues', count($rows)));

        // 2. Exécution
        $io->section('2. Exécution');
        $stats = $this->executeUpdates($rows, $io, $dryRun);

        // 3. Log + synthèse
        $this->writeLog($dir, $stats, $dryRun);
        $io->section('3. Synthèse');
        $io->table(
            ['Mesure', 'Valeur'],
            [
                ['Mode',                                      $dryRun ? 'DRY-RUN (ROLLBACK)' : 'RÉEL (COMMIT)'],
                ['UPDATE planifiés',                          (string) $stats['planned']],
                ['UPDATE exécutés',                           (string) $stats['executed']],
                ['Lignes sans champ à mettre à jour',         (string) $stats['skipped_empty']],
                ['Erreurs SQL',                               (string) $stats['errors']],
            ]
        );

        if ($stats['errors'] > 0) {
            $io->error('Des erreurs SQL sont survenues — transaction annulée.');
            return Command::FAILURE;
        }
        $io->success(sprintf('%s.', $dryRun ? 'Dry-run terminé sans erreur' : 'UPDATE appliqués avec succès'));
        return Command::SUCCESS;
    }

    // =====================================================================
    // Lecture
    // =====================================================================

    /**
     * @return list<array{provider_code:int, siren:string, numero_tva:string, code_naf:string}>
     */
    private function readToUpdate(string $path, string $delimiter): array
    {
        $h = fopen($path, 'rb');
        if ($h === false) {
            throw new RuntimeException('Ouverture impossible : ' . $path);
        }
        $headers = fgetcsv($h, 0, $delimiter, '"', '\\');
        if ($headers === false) {
            fclose($h);
            return [];
        }
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]) ?? $headers[0];
        $headers = array_map(static fn(string $s): string => strtolower(trim($s)), $headers);

        $out = [];
        while (($row = fgetcsv($h, 0, $delimiter, '"', '\\')) !== false) {
            if ($row === [null] || $row === []) {
                continue;
            }
            $assoc = [];
            foreach ($headers as $i => $name) {
                $assoc[$name] = (string) ($row[$i] ?? '');
            }
            $code = (int) ($assoc['provider_code'] ?? 0);
            if ($code <= 0) {
                continue;
            }
            $out[] = [
                'provider_code' => $code,
                'siren'         => trim((string) ($assoc['siren_new'] ?? '')),
                'numero_tva'    => trim((string) ($assoc['numero_tva_new'] ?? '')),
                'code_naf'      => trim((string) ($assoc['code_naf_new'] ?? '')),
            ];
        }
        fclose($h);
        return $out;
    }

    // =====================================================================
    // Exécution
    // =====================================================================

    /**
     * @param list<array{provider_code:int, siren:string, numero_tva:string, code_naf:string}> $rows
     * @return array{planned:int, executed:int, skipped_empty:int, errors:int, error_details:list<string>}
     */
    private function executeUpdates(array $rows, SymfonyStyle $io, bool $dryRun): array
    {
        $stats = [
            'planned' => count($rows),
            'executed' => 0,
            'skipped_empty' => 0,
            'errors' => 0,
            'error_details' => [],
        ];

        $progress = $io->createProgressBar(count($rows));
        $progress->setFormat(' %current%/%max% [%bar%] %elapsed%');
        $progress->start();

        $this->pdo->beginTransaction();
        try {
            foreach ($rows as $row) {
                $sets = [];
                $params = [];
                foreach (self::FISCAL_FIELDS as $field) {
                    $value = $row[$field];
                    if ($value === '') {
                        continue;
                    }
                    $sets[] = sprintf('provider_%s = :%s', $field, $field);
                    $params[':' . $field] = $value;
                }
                if ($sets === []) {
                    $stats['skipped_empty']++;
                    $progress->advance();
                    continue;
                }
                $params[':code'] = $row['provider_code'];
                $sql = sprintf(
                    'UPDATE %sprovider SET %s WHERE provider_code = :code',
                    $this->schemaPrefix,
                    implode(', ', $sets)
                );
                try {
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute($params);
                    $stats['executed']++;
                } catch (\Throwable $e) {
                    $stats['errors']++;
                    $stats['error_details'][] = sprintf(
                        'provider_code=%d : %s',
                        $row['provider_code'],
                        $e->getMessage()
                    );
                }
                $progress->advance();
            }
            $progress->finish();
            $io->newLine(2);

            if ($stats['errors'] > 0) {
                $this->pdo->rollBack();
                $io->error('Erreurs détectées → ROLLBACK.');
            } elseif ($dryRun) {
                $this->pdo->rollBack();
                $io->note('Dry-run : ROLLBACK appliqué.');
            } else {
                $this->pdo->commit();
                $io->success('COMMIT effectué.');
            }
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $stats['errors']++;
            $stats['error_details'][] = 'Erreur fatale : ' . $e->getMessage();
            $io->error('Erreur fatale, transaction annulée : ' . $e->getMessage());
        }
        return $stats;
    }

    /**
     * @param array{planned:int, executed:int, skipped_empty:int, errors:int, error_details:list<string>} $stats
     */
    private function writeLog(string $dir, array $stats, bool $dryRun): void
    {
        $path = $dir . DIRECTORY_SEPARATOR . self::FILE_LOG;
        $content = sprintf(
            "Phase 2c — Log d'exécution\n" .
            "Date            : %s\n" .
            "Mode            : %s\n" .
            "Planifiés       : %d\n" .
            "Exécutés        : %d\n" .
            "Sans changement : %d\n" .
            "Erreurs SQL     : %d\n",
            date('Y-m-d H:i:s'),
            $dryRun ? 'DRY-RUN (ROLLBACK)' : 'REEL (COMMIT)',
            $stats['planned'],
            $stats['executed'],
            $stats['skipped_empty'],
            $stats['errors']
        );
        if ($stats['errors'] > 0) {
            $content .= "\n--- Détail erreurs ---\n";
            foreach ($stats['error_details'] as $err) {
                $content .= '  - ' . $err . "\n";
            }
        }
        file_put_contents($path, $content);
    }
}
