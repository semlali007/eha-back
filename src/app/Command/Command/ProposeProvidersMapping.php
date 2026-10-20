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
 * Phase 2a — Propose un mapping entre un CSV de référence fournisseurs
 * (INSEE/Sirene-like) et la table provider, SANS rien modifier en base.
 *
 * Architecture en 2 passes pour limiter la RAM sur les gros CSV :
 *   Pass 1 — Scan du CSV pour identifier le SIREN gagnant (= ligne CSV la
 *            plus complète) parmi les lignes partageant un même SIREN.
 *            Mémoire bornée à ~10 Mo / 500K SIRENs.
 *   Pass 2 — Re-lecture du CSV, on ne classifie QUE les rows gagnantes
 *            (+ les rows sans SIREN, conservées telles quelles). Pour chaque
 *            row : matching contre les providers BDD par label normalisé,
 *            auto-résolution des ambig BDD, consolidation par target.
 *
 * Sortie : 01_to_update.csv = exactement 1 ligne par provider_code BDD matché.
 *
 * Usage :
 *   bin/console app:providers:propose-mapping <csv-path>
 *               [--out=/tmp/eha-providers] [--delimiter=,]
 */
class ProposeProvidersMapping extends Command
{
    protected static $defaultName = 'app:providers:propose-mapping';

    /** Colonnes attendues dans le CSV (insensible à la casse). */
    private const REQUIRED_COLUMNS = [
        'nom_fournisseur',
        'siren',
        'numero_tva',
        'code_naf_etablissement',
    ];

    /**
     * Suffixes légaux français à supprimer en fin de label normalisé.
     * Ordre du plus long au plus court (sas avant sa).
     */
    private const LEGAL_SUFFIXES = [
        'selarl', 'sasu', 'sarl', 'sccv', 'scop', 'scea', 'eirl',
        'sas',  'sci', 'snc', 'scs', 'sca', 'eurl', 'gie',
        'sa',   'se',  'sc',  'ei',  'sem', 'ets', 'cie',
    ];

    /** Stop-words FR à supprimer du label tokenisé (articles, prépositions). */
    private const STOP_WORDS = [
        'et', 'de', 'du', 'des', 'la', 'le', 'les',
        'l', 'd', 'a', 'au', 'aux', 'en', 'par', 'pour',
        'avec', 'sur', 'sous', 'sans',
    ];

    private PDO $pdo;

    protected function configure(): void
    {
        $this
            ->setDescription('Phase 2a — Propose un mapping CSV → provider sans rien modifier (dry-run total).')
            ->addArgument('csv-path', InputArgument::REQUIRED, 'Chemin du CSV source (UTF-8)')
            ->addOption(
                'out',
                'o',
                InputOption::VALUE_REQUIRED,
                'Répertoire de sortie pour 01_to_update.csv et 00_stats.txt',
                sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'eha-providers'
            )
            ->addOption(
                'delimiter',
                'd',
                InputOption::VALUE_REQUIRED,
                'Séparateur CSV (auto-détecté si non précisé)',
                null
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->pdo = (new DBConnection())->getPDO();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Pass 1 bufferise tous les SIRENs uniques en RAM (~10 Mo pour 500K).
        @ini_set('memory_limit', '1G');

        $io = new SymfonyStyle($input, $output);
        $io->title('Phase 2a — Proposition de mapping (read-only)');

        $csvPath = (string) $input->getArgument('csv-path');
        $outDir = (string) $input->getOption('out');
        $delimiter = $input->getOption('delimiter');

        if (!is_readable($csvPath)) {
            $io->error(sprintf('CSV introuvable ou illisible : %s', $csvPath));
            return Command::FAILURE;
        }
        if (!is_dir($outDir) && !mkdir($outDir, 0o755, true) && !is_dir($outDir)) {
            $io->error(sprintf('Impossible de créer le répertoire de sortie : %s', $outDir));
            return Command::FAILURE;
        }
        if ($delimiter === null) {
            $delimiter = $this->detectDelimiter($csvPath);
            $io->writeln(sprintf('  Séparateur auto-détecté : <info>%s</info>', $delimiter === "\t" ? '\\t' : $delimiter));
        }

        // 1) Charge BDD
        $io->section('1. Chargement des providers BDD');
        $bddByNorm = $this->loadProvidersByNormalizedLabel();
        $totalBdd = array_sum(array_map('count', $bddByNorm));
        $clusters = count($bddByNorm);
        $duplicateClusters = count(array_filter($bddByNorm, static fn(array $g): bool => count($g) > 1));
        $io->writeln(sprintf(
            '  %d providers chargés, %d clusters distincts (label normalisé), %d clusters avec doublons',
            $totalBdd,
            $clusters,
            $duplicateClusters
        ));

        // 2) Ouvre le CSV + résout les indices de colonnes
        $io->section('2. Lecture du CSV d\'entrée');
        $handle = fopen($csvPath, 'rb');
        if ($handle === false) {
            $io->error('Impossible d\'ouvrir le CSV.');
            return Command::FAILURE;
        }
        $headerRow = $this->readCsvRow($handle, $delimiter);
        if ($headerRow === null) {
            $io->error('CSV vide.');
            fclose($handle);
            return Command::FAILURE;
        }
        $headerRow[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headerRow[0]) ?? $headerRow[0];
        try {
            $colIdx = $this->resolveColumnIndices($headerRow);
        } catch (RuntimeException $e) {
            $io->error($e->getMessage());
            fclose($handle);
            return Command::FAILURE;
        }
        $io->writeln(sprintf('  En-tête lu, %d colonnes utiles localisées.', count($colIdx)));

        // 3) Pass 1 — dédup CSV par SIREN
        $io->section('3. Pass 1 — dédup CSV par SIREN');
        $sirenStats = ['unique_sirens' => 0, 'no_siren_rows' => 0, 'consolidated_count' => 0];
        $sirenWinners = $this->buildSirenWinners($handle, $delimiter, $colIdx, $io, $sirenStats);
        $io->writeln(sprintf(
            '  %d SIRENs distincts | %d rows sans SIREN | %d rows consolidées (perdantes)',
            $sirenStats['unique_sirens'],
            $sirenStats['no_siren_rows'],
            $sirenStats['consolidated_count']
        ));

        // Rewind + réabsorbe le header
        rewind($handle);
        $this->readCsvRow($handle, $delimiter);

        // 4) Pass 2 — classification + consolidation par target
        $io->section('4. Pass 2 — classification');
        $outHandle = $this->openOutputCsv($outDir);

        $byTarget = []; // code => {bdd, csv, bdd_match_count, csv_match_count}
        $counts = [
            'csv_rows' => 0,
            'to_update_targets' => 0,
            'to_update_with_changes' => 0,
            'auto_resolved' => 0,
            'target_consolidated' => 0,
            'no_match' => 0,
            'skipped_empty_label' => 0,
            'skipped_by_siren_dedup' => $sirenStats['consolidated_count'],
        ];

        $progress = $io->createProgressBar();
        $progress->setFormat(' %current% lignes lues [%bar%] %elapsed%');
        $progress->start();

        $rowNum = 1;
        while (($row = $this->readCsvRow($handle, $delimiter)) !== null) {
            $rowNum++;
            $counts['csv_rows']++;
            if ($counts['csv_rows'] % 5000 === 0) {
                $progress->setProgress($counts['csv_rows']);
            }

            $label = trim($row[$colIdx['nom_fournisseur']] ?? '');
            if ($label === '') {
                $counts['skipped_empty_label']++;
                continue;
            }
            $siren = trim($row[$colIdx['siren']] ?? '');
            if ($siren !== '' && ($sirenWinners[$siren] ?? null) !== $rowNum) {
                continue; // perdante de la dédup SIREN
            }

            $csvData = [
                'nom_fournisseur' => $label,
                'siren' => $siren,
                'numero_tva' => trim($row[$colIdx['numero_tva']] ?? ''),
                'code_naf' => trim($row[$colIdx['code_naf_etablissement']] ?? ''),
            ];

            $norm = self::normalizeLabel($label);
            $matches = $bddByNorm[$norm] ?? [];
            if ($matches === []) {
                $counts['no_match']++;
                continue;
            }

            $bdd = $this->selectBestBddMatch($matches);
            $code = (int) $bdd['provider_code'];

            if (!isset($byTarget[$code])) {
                $byTarget[$code] = [
                    'bdd' => $bdd,
                    'csv' => $csvData,
                    'bdd_match_count' => count($matches),
                    'csv_match_count' => 1,
                ];
            } else {
                $entry = $byTarget[$code];
                $entry['csv_match_count']++;
                if ($this->csvCompleteness($csvData) > $this->csvCompleteness($entry['csv'])) {
                    $entry['bdd'] = $bdd;
                    $entry['csv'] = $csvData;
                    $entry['bdd_match_count'] = count($matches);
                }
                $byTarget[$code] = $entry;
                $counts['target_consolidated']++;
            }
        }
        $progress->finish();
        $io->newLine(2);

        // 5) Flush 01_to_update.csv
        $io->section('5. Flush 01_to_update.csv');
        foreach ($byTarget as $entry) {
            $changes = $this->diffFields($entry['bdd'], $entry['csv']);
            $this->writeToUpdateRow(
                $outHandle,
                $entry['bdd'],
                $entry['csv'],
                $changes,
                $entry['bdd_match_count'],
                $entry['csv_match_count']
            );
            $counts['to_update_targets']++;
            if ($changes > 0) {
                $counts['to_update_with_changes']++;
            }
            if ($entry['bdd_match_count'] > 1) {
                $counts['auto_resolved']++;
            }
        }
        fclose($outHandle);
        fclose($handle);

        // 6) Stats
        $this->writeStatsFile($outDir, $counts, $totalBdd, $clusters, $duplicateClusters, $sirenStats);
        $io->section('6. Synthèse');
        $io->table(
            ['Mesure', 'Valeur'],
            [
                ['Providers BDD',                            (string) $totalBdd],
                ['  → matchés (= targets dans to_update)',   (string) $counts['to_update_targets']],
                ['  → non matchés (resteront NULL en BDD)',  (string) ($totalBdd - $counts['to_update_targets'])],
                ['', ''],
                ['CSV rows lues (pass 2)',                   (string) $counts['csv_rows']],
                ['  → skipped (label vide)',                 (string) $counts['skipped_empty_label']],
                ['  → skipped (dédup SIREN)',                (string) $counts['skipped_by_siren_dedup']],
                ['  → sans match BDD (ignorées)',            (string) $counts['no_match']],
                ['  → consolidées vers un target déjà vu',   (string) $counts['target_consolidated']],
                ['', ''],
                ['UPDATE proposés (à appliquer en Phase 2c)',(string) $counts['to_update_targets']],
                ['  dont ≥ 1 changement effectif',           (string) $counts['to_update_with_changes']],
                ['  dont BDD initialement ambigu (N>1)',     (string) $counts['auto_resolved']],
            ]
        );

        $io->success(sprintf('Mapping proposé dans : %s', $outDir));
        $io->note('Aucune modification BDD. Inspecte 01_to_update.csv puis lance app:providers:apply-mapping.');

        return Command::SUCCESS;
    }

    // =====================================================================
    // BDD
    // =====================================================================

    /**
     * @return array<string, list<array<string,?string>>>  normalized_label => list of providers
     */
    private function loadProvidersByNormalizedLabel(): array
    {
        $schema = (new DBConnection())->getSchema();
        $sql = sprintf(
            'SELECT provider_code, provider_label, provider_siren, provider_numero_tva, provider_code_naf
             FROM %sprovider',
            $schema
        );
        $stmt = $this->pdo->query($sql);
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $map = [];
        foreach ($stmt as $row) {
            $norm = self::normalizeLabel((string) $row['provider_label']);
            $map[$norm][] = $row;
        }
        return $map;
    }

    /**
     * Sélection canonique parmi N candidats BDD partageant le même label
     * normalisé : 1) plus de champs non-vides DESC, 2) provider_code ASC.
     *
     * @param list<array<string,?string>> $matches
     * @return array<string,?string>
     */
    private function selectBestBddMatch(array $matches): array
    {
        usort($matches, function (array $a, array $b): int {
            $ca = $this->countNonEmptyBddFields($a);
            $cb = $this->countNonEmptyBddFields($b);
            if ($ca !== $cb) {
                return $cb <=> $ca;
            }
            return ((int) $a['provider_code']) <=> ((int) $b['provider_code']);
        });
        return $matches[0];
    }

    /**
     * @param array<string,?string> $row
     */
    private function countNonEmptyBddFields(array $row): int
    {
        $fields = ['provider_label', 'provider_siren', 'provider_numero_tva', 'provider_code_naf'];
        $c = 0;
        foreach ($fields as $f) {
            $v = $row[$f] ?? null;
            if ($v !== null && trim((string) $v) !== '') {
                $c++;
            }
        }
        return $c;
    }

    // =====================================================================
    // Normalisation
    // =====================================================================

    /**
     * Normalisation pour le matching BDD ↔ CSV :
     *   1. strip du contenu entre parenthèses (acronymes, commentaires)
     *   2. esperluettes (&, ＆ fullwidth) → espace
     *   3. lower + translit accents
     *   4. ponctuation → espace + collapse whitespace
     *   5. strip itératif suffixes légaux français en fin (SA, SAS, SARL...)
     *   6. tokenize + strip stop-words FR + sort
     */
    public static function normalizeLabel(string $label): string
    {
        $s = trim($label);
        if ($s === '') {
            return '';
        }
        $s = preg_replace('/\([^)]*\)/u', ' ', $s) ?? $s;
        $s = preg_replace('/[&\x{FF06}]/u', ' ', $s) ?? $s;
        $s = mb_strtolower($s, 'UTF-8');
        $translit = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        if ($translit !== false) {
            $s = strtolower($translit);
        }
        $s = preg_replace('/[^a-z0-9]+/u', ' ', $s) ?? $s;
        $s = preg_replace('/\s+/', ' ', $s) ?? $s;
        $s = trim($s);
        if ($s === '') {
            return '';
        }
        $s = self::stripLegalSuffixes($s);
        if ($s === '') {
            return '';
        }
        $tokens = explode(' ', $s);
        $stopSet = array_flip(self::STOP_WORDS);
        $tokens = array_values(array_filter(
            $tokens,
            static fn(string $t): bool => $t !== '' && !isset($stopSet[$t])
        ));
        sort($tokens, SORT_STRING);
        return implode(' ', $tokens);
    }

    private static function stripLegalSuffixes(string $s): string
    {
        $changed = true;
        while ($changed) {
            $changed = false;
            foreach (self::LEGAL_SUFFIXES as $sfx) {
                $pattern = '/^(.+?)\s+' . preg_quote($sfx, '/') . '$/';
                if (preg_match($pattern, $s, $m) === 1) {
                    $s = trim($m[1]);
                    $changed = true;
                    break;
                }
            }
        }
        return $s;
    }

    // =====================================================================
    // Pass 1 — dédup CSV par SIREN
    // =====================================================================

    /**
     * @param resource $handle
     * @param array<string,int> $colIdx
     * @param array{unique_sirens:int, no_siren_rows:int, consolidated_count:int} $stats
     * @return array<string,int>  siren => row_num du gagnant
     */
    private function buildSirenWinners($handle, string $delimiter, array $colIdx, SymfonyStyle $io, array &$stats): array
    {
        $winners = []; // siren => [row_num, score]
        $rowNum = 1;
        $rowsRead = 0;
        $progress = $io->createProgressBar();
        $progress->setFormat(' %current% lignes scann\xC3\xA9es [%bar%] %elapsed%');
        $progress->start();
        while (($row = $this->readCsvRow($handle, $delimiter)) !== null) {
            $rowNum++;
            $rowsRead++;
            if ($rowsRead % 5000 === 0) {
                $progress->setProgress($rowsRead);
            }
            $siren = trim($row[$colIdx['siren']] ?? '');
            if ($siren === '') {
                $stats['no_siren_rows']++;
                continue;
            }
            $score = $this->scoreCsvRowCompleteness($row, $colIdx);
            if (!isset($winners[$siren])) {
                $winners[$siren] = [$rowNum, $score];
            } elseif ($score > $winners[$siren][1]) {
                $stats['consolidated_count']++;
                $winners[$siren] = [$rowNum, $score];
            } else {
                $stats['consolidated_count']++;
            }
        }
        $progress->finish();
        $io->newLine(2);
        $stats['unique_sirens'] = count($winners);
        return array_map(static fn(array $v): int => $v[0], $winners);
    }

    /**
     * @param list<string> $row
     * @param array<string,int> $colIdx
     */
    private function scoreCsvRowCompleteness(array $row, array $colIdx): int
    {
        $s = 0;
        if (trim($row[$colIdx['numero_tva']] ?? '') !== '') {
            $s++;
        }
        if (trim($row[$colIdx['code_naf_etablissement']] ?? '') !== '') {
            $s++;
        }
        return $s;
    }

    /**
     * @param array<string,string> $csv
     */
    private function csvCompleteness(array $csv): int
    {
        $s = 0;
        foreach (['siren', 'numero_tva', 'code_naf'] as $f) {
            if (($csv[$f] ?? '') !== '') {
                $s++;
            }
        }
        return $s;
    }

    // =====================================================================
    // CSV : lecture/écriture
    // =====================================================================

    private function detectDelimiter(string $csvPath): string
    {
        $handle = fopen($csvPath, 'rb');
        if ($handle === false) {
            return ',';
        }
        $first = fgets($handle);
        fclose($handle);
        if ($first === false || $first === '') {
            return ',';
        }
        $first = preg_replace('/^\xEF\xBB\xBF/', '', $first) ?? $first;
        $cands = [
            ',' => substr_count($first, ','),
            ';' => substr_count($first, ';'),
            "\t" => substr_count($first, "\t"),
            '|' => substr_count($first, '|'),
        ];
        arsort($cands);
        $best = array_key_first($cands);
        return $cands[$best] > 0 ? (string) $best : ',';
    }

    /**
     * @param resource $handle
     * @return list<string>|null  null = EOF
     */
    private function readCsvRow($handle, string $delimiter): ?array
    {
        $row = fgetcsv($handle, 0, $delimiter, '"', '\\');
        if ($row === false || $row === null) {
            return null;
        }
        if (count($row) === 1 && ($row[0] === null || $row[0] === '')) {
            return [];
        }
        return $row;
    }

    /**
     * @param list<string> $headerRow
     * @return array<string,int>
     */
    private function resolveColumnIndices(array $headerRow): array
    {
        $normalized = array_map(static fn(string $h): string => strtolower(trim($h)), $headerRow);
        $idx = [];
        foreach (self::REQUIRED_COLUMNS as $col) {
            $pos = array_search($col, $normalized, true);
            if ($pos === false) {
                throw new RuntimeException(sprintf(
                    'Colonne requise "%s" introuvable dans l\'en-tête. En-tête lu : [%s]',
                    $col,
                    implode(', ', $normalized)
                ));
            }
            $idx[$col] = $pos;
        }
        return $idx;
    }

    /** @return resource */
    private function openOutputCsv(string $outDir)
    {
        $path = $outDir . DIRECTORY_SEPARATOR . '01_to_update.csv';
        $h = fopen($path, 'wb');
        if ($h === false) {
            throw new RuntimeException('Écriture impossible : ' . $path);
        }
        fputcsv($h, [
            'provider_code',
            'provider_label_bdd',
            'provider_label_csv',
            'siren_current',
            'siren_new',
            'numero_tva_current',
            'numero_tva_new',
            'code_naf_current',
            'code_naf_new',
            'changes_count',
            'bdd_match_count',
            'csv_match_count',
        ], ';');
        return $h;
    }

    /**
     * @param resource $h
     * @param array<string,?string> $bdd
     * @param array<string,string> $csv
     */
    private function writeToUpdateRow($h, array $bdd, array $csv, int $changes, int $bddMatchCount, int $csvMatchCount): void
    {
        fputcsv($h, [
            (string) $bdd['provider_code'],
            (string) $bdd['provider_label'],
            $csv['nom_fournisseur'],
            (string) ($bdd['provider_siren'] ?? ''),
            $csv['siren'],
            (string) ($bdd['provider_numero_tva'] ?? ''),
            $csv['numero_tva'],
            (string) ($bdd['provider_code_naf'] ?? ''),
            $csv['code_naf'],
            (string) $changes,
            (string) $bddMatchCount,
            (string) $csvMatchCount,
        ], ';');
    }

    /**
     * @param array<string,?string> $bdd
     * @param array<string,string> $csv
     */
    private function diffFields(array $bdd, array $csv): int
    {
        $pairs = [
            ['provider_siren', 'siren'],
            ['provider_numero_tva', 'numero_tva'],
            ['provider_code_naf', 'code_naf'],
        ];
        $c = 0;
        foreach ($pairs as [$bk, $ck]) {
            $cur = (string) ($bdd[$bk] ?? '');
            $new = $csv[$ck];
            if ($new !== '' && $new !== $cur) {
                $c++;
            }
        }
        return $c;
    }

    /**
     * @param array<string,int> $counts
     * @param array{unique_sirens:int, no_siren_rows:int, consolidated_count:int} $sirenStats
     */
    private function writeStatsFile(string $outDir, array $counts, int $totalBdd, int $clusters, int $dupClusters, array $sirenStats): void
    {
        $content = sprintf(
            "Phase 2a — Proposition de mapping CSV → provider\n" .
            "Généré le : %s\n\n" .
            "BDD\n" .
            "  Providers chargés                    : %d\n" .
            "  Clusters distincts (label normalisé) : %d\n" .
            "  Clusters avec doublons internes      : %d\n\n" .
            "Pass 1 — Dédup CSV par SIREN\n" .
            "  SIRENs uniques                       : %d\n" .
            "  Rows sans SIREN (toutes conservées)  : %d\n" .
            "  Rows consolidées (perdantes/SIREN)   : %d\n\n" .
            "Pass 2 — Classification\n" .
            "  Lignes lues                          : %d\n" .
            "    → to_update targets                : %d\n" .
            "        dont ≥ 1 changement effectif   : %d\n" .
            "        dont BDD initialement ambigu   : %d\n" .
            "        dont CSV consolidées/target    : %d\n" .
            "    → sans match BDD (ignorées)        : %d\n" .
            "    → skipped (label vide)             : %d\n",
            date('Y-m-d H:i:s'),
            $totalBdd,
            $clusters,
            $dupClusters,
            $sirenStats['unique_sirens'],
            $sirenStats['no_siren_rows'],
            $sirenStats['consolidated_count'],
            $counts['csv_rows'],
            $counts['to_update_targets'],
            $counts['to_update_with_changes'],
            $counts['auto_resolved'],
            $counts['target_consolidated'],
            $counts['no_match'],
            $counts['skipped_empty_label']
        );
        file_put_contents($outDir . DIRECTORY_SEPARATOR . '00_stats.txt', $content);
    }
}
