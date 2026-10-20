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
 * Diagnostic post-Phase-2a — Pour chaque provider BDD dont le label normalisé
 * n'apparaît pas dans le CSV (= non matché par Phase 2a), trouve les top-N
 * labels CSV les plus similaires via Jaccard sur tokens.
 *
 * Permet de classer les non-match :
 *   (a) Vraie absence : aucun candidat → l'entreprise n'est pas dans le CSV
 *   (b) Pattern raté  : 1 candidat à score ≥ 0.8 → un cas qu'on n'a pas su capter
 *
 * 100% read-only (1 SELECT + 2 passes sur le CSV en lecture). Aucune
 * modification BDD. Safe à lancer sur prod.
 *
 * Usage :
 *   bin/console app:providers:diagnose-unmatched <csv-path>
 *               [--out=...] [--top-n=3] [--delimiter=,] [--min-score=0.0]
 */
class DiagnoseUnmatchedProviders extends Command
{
    protected static $defaultName = 'app:providers:diagnose-unmatched';

    private const FILE_OUTPUT = '03_diagnose_unmatched.csv';

    private PDO $pdo;

    protected function configure(): void
    {
        $this
            ->setDescription('Diagnostique les providers BDD non matchés par Phase 2a (Jaccard sur tokens). Read-only.')
            ->addArgument('csv-path', InputArgument::REQUIRED, 'Chemin du CSV source (le même que Phase 2a)')
            ->addOption(
                'out',
                'o',
                InputOption::VALUE_REQUIRED,
                'Répertoire de sortie pour 03_diagnose_unmatched.csv',
                sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'eha-providers'
            )
            ->addOption(
                'top-n',
                null,
                InputOption::VALUE_REQUIRED,
                'Nombre de candidats CSV à retenir par BDD non matché',
                '3'
            )
            ->addOption(
                'delimiter',
                'd',
                InputOption::VALUE_REQUIRED,
                'Séparateur CSV (auto-détecté si non précisé)',
                null
            )
            ->addOption(
                'min-score',
                null,
                InputOption::VALUE_REQUIRED,
                'Seuil Jaccard minimum dans le rapport 03_diagnose_unmatched.csv (0.0 = tout garder)',
                '0.0'
            )
            ->addOption(
                'threshold',
                null,
                InputOption::VALUE_REQUIRED,
                'Si > 0 : produit AUSSI 04_to_update_from_diagnostic.csv (format Phase 2a) avec les rows dont candidate_1_score > threshold. Strictement supérieur.',
                '0.0'
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->pdo = (new DBConnection())->getPDO();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        @ini_set('memory_limit', '1G');

        $io = new SymfonyStyle($input, $output);
        $io->title('Diagnostic — providers BDD non matchés');

        $csvPath = (string) $input->getArgument('csv-path');
        $outDir = (string) $input->getOption('out');
        $topN = max(1, (int) $input->getOption('top-n'));
        $minScore = (float) $input->getOption('min-score');
        $applyThreshold = (float) $input->getOption('threshold');
        $delimiter = $input->getOption('delimiter');

        if (!is_readable($csvPath)) {
            $io->error(sprintf('CSV introuvable : %s', $csvPath));
            return Command::FAILURE;
        }
        if (!is_dir($outDir) && !mkdir($outDir, 0o755, true) && !is_dir($outDir)) {
            $io->error(sprintf('Répertoire de sortie inaccessible : %s', $outDir));
            return Command::FAILURE;
        }
        if ($delimiter === null) {
            $delimiter = $this->detectDelimiter($csvPath);
            $io->writeln(sprintf('  Séparateur auto-détecté : <info>%s</info>', $delimiter === "\t" ? '\\t' : $delimiter));
        }

        // 1) Charger BDD
        $io->section('1. Chargement BDD');
        $bdd = $this->loadBdd();
        $io->writeln(sprintf('  %d providers chargés', count($bdd)));

        // 2) Pass 1 CSV : set des labels normalisés présents
        $io->section('2. Pass 1 — set des labels CSV normalisés');
        $csvNormalizedSet = $this->buildCsvNormalizedSet($csvPath, $delimiter, $io);
        $io->writeln(sprintf('  %d labels CSV normalisés distincts', count($csvNormalizedSet)));

        // 2bis) Calcul des IDF par token (= rareté dans le CSV)
        //       Sert à pondérer le Jaccard : "ricoh" (rare) > "consulting" (commun).
        $io->section('2bis. Calcul IDF par token');
        $tokenIdf = $this->computeTokenIdf($csvNormalizedSet);
        $io->writeln(sprintf('  IDF calculé pour %d tokens uniques', count($tokenIdf)));

        // 3) Filtrer : unmatched = label normalisé BDD absent du set CSV
        $unmatched = [];
        foreach ($bdd as $row) {
            if (!isset($csvNormalizedSet[$row['normalized']])) {
                $unmatched[] = $row;
            }
        }
        unset($csvNormalizedSet);
        $io->writeln(sprintf('  → <info>%d providers BDD non matchés</info> à diagnostiquer', count($unmatched)));
        if ($unmatched === []) {
            $io->success('Aucun BDD non matché. Rien à diagnostiquer.');
            return Command::SUCCESS;
        }

        // 4) Tokenize + index inversé token → list unmatched_idx
        $io->section('3. Tokenisation + index inversé des BDD non matchés');
        $tokenIndex = [];
        foreach ($unmatched as $idx => &$row) {
            $row['tokens'] = $this->tokenize($row['normalized']);
            $row['token_set'] = array_flip($row['tokens']);
            foreach ($row['token_set'] as $token => $_) {
                $tokenIndex[$token][] = $idx;
            }
        }
        unset($row);
        $io->writeln(sprintf('  %d tokens uniques dans les non matchés', count($tokenIndex)));

        // 5) Pass 2 CSV : scoring Jaccard pondéré par IDF
        $io->section('4. Pass 2 — scoring Jaccard pondéré IDF');
        $topPerBdd = [];
        $this->scoreCsvAgainstUnmatched($csvPath, $delimiter, $unmatched, $tokenIndex, $tokenIdf, $topN, $minScore, $topPerBdd, $io);

        // 6) Sortie du rapport diagnostique
        $io->section('5. Écriture du rapport');
        $outPath = $outDir . DIRECTORY_SEPARATOR . self::FILE_OUTPUT;
        $this->writeReport($outPath, $unmatched, $topPerBdd, $topN, $delimiter);

        $withCandidates = 0;
        foreach ($topPerBdd as $list) {
            if ($list !== []) {
                $withCandidates++;
            }
        }

        // 7) Si --threshold > 0, produit AUSSI un fichier applicable
        //    par apply-mapping --file=04_to_update_from_diagnostic.csv
        $appliedCount = 0;
        $applyPath = null;
        if ($applyThreshold > 0.0) {
            $applyPath = $outDir . DIRECTORY_SEPARATOR . '04_to_update_from_diagnostic.csv';
            $appliedCount = $this->writeApplyCsv($applyPath, $unmatched, $topPerBdd, $applyThreshold);
            $io->writeln(sprintf(
                '  <info>%d rows</info> écrites dans 04_to_update_from_diagnostic.csv (score > %.3f)',
                $appliedCount,
                $applyThreshold
            ));
        }

        $io->table(
            ['Mesure', 'Valeur'],
            [
                ['BDD non matchés',                                              (string) count($unmatched)],
                ['  → avec ≥ 1 candidat CSV (score > ' . $minScore . ')',       (string) $withCandidates],
                ['  → sans aucun candidat (vraies "absences")',                  (string) (count($unmatched) - $withCandidates)],
                ['Rapport diagnostique',                                         $outPath],
                ['Fichier applicable produit (threshold > 0)',                   $applyPath === null ? '— (--threshold non précisé)' : $applyPath],
                ['  → rows applicables (candidate_1_score > ' . $applyThreshold . ')', (string) $appliedCount],
            ]
        );
        $io->success('Diagnostic terminé. Trié par best-score DESC : les loupés les plus probables sont en haut.');
        if ($applyPath !== null && $appliedCount > 0) {
            $io->note(sprintf(
                "Pour appliquer ces matches :\n" .
                "  php bin/console app:providers:apply-mapping %s --file=04_to_update_from_diagnostic.csv --dry-run\n" .
                "  (puis sans --dry-run si OK)",
                $outDir
            ));
        }

        return Command::SUCCESS;
    }

    /**
     * Écrit un CSV au FORMAT 01_to_update.csv (Phase 2a) — directement utilisable
     * par apply-mapping --file=04_to_update_from_diagnostic.csv.
     *
     * Ne garde QUE les BDD dont le meilleur candidat a un score STRICTEMENT
     * supérieur au seuil.
     *
     * @param list<array{provider_code:int, provider_label:string, normalized:string}> $unmatched
     * @param array<int, list<array{0:float,1:string,2:string,3:string,4:string,5:string}>> $topPerBdd
     * @return int  nb de lignes écrites
     */
    private function writeApplyCsv(string $path, array $unmatched, array $topPerBdd, float $threshold): int
    {
        $h = fopen($path, 'wb');
        if ($h === false) {
            throw new RuntimeException('Écriture impossible : ' . $path);
        }
        // Header identique à 01_to_update.csv produit par Phase 2a
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

        $count = 0;
        foreach ($unmatched as $idx => $bddRow) {
            $top = $topPerBdd[$idx] ?? [];
            if ($top === []) {
                continue;
            }
            [$score, $candidateLabel, $candidateNorm, $siren, $tva, $naf] = $top[0];
            if ($score <= $threshold) {
                continue;
            }
            // changes_count : nb de colonnes fiscales non-vides côté CSV
            $changes = 0;
            if ($siren !== '') {
                $changes++;
            }
            if ($tva !== '') {
                $changes++;
            }
            if ($naf !== '') {
                $changes++;
            }
            fputcsv($h, [
                (string) $bddRow['provider_code'],
                $bddRow['provider_label'],
                $candidateLabel,
                '',           // siren_current : par construction non matché donc vide BDD
                $siren,
                '',
                $tva,
                '',
                $naf,
                (string) $changes,
                '1',          // bdd_match_count
                '1',          // csv_match_count
            ], ';');
            $count++;
        }
        fclose($h);
        return $count;
    }

    // =====================================================================
    // BDD
    // =====================================================================

    /**
     * @return list<array{provider_code:int, provider_label:string, normalized:string}>
     */
    private function loadBdd(): array
    {
        $schema = (new DBConnection())->getSchema();
        $sql = sprintf('SELECT provider_code, provider_label FROM %sprovider', $schema);
        $stmt = $this->pdo->query($sql);
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($stmt as $row) {
            $label = (string) $row['provider_label'];
            $out[] = [
                'provider_code' => (int) $row['provider_code'],
                'provider_label' => $label,
                'normalized' => ProposeProvidersMapping::normalizeLabel($label),
            ];
        }
        return $out;
    }

    // =====================================================================
    // Pass 1 CSV — set des labels normalisés
    // =====================================================================

    /**
     * @return array<string, true>
     */
    private function buildCsvNormalizedSet(string $csvPath, string $delimiter, SymfonyStyle $io): array
    {
        $handle = fopen($csvPath, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Ouverture impossible : ' . $csvPath);
        }
        $headerRow = fgetcsv($handle, 0, $delimiter, '"', '\\');
        if ($headerRow === false) {
            fclose($handle);
            throw new RuntimeException('CSV vide');
        }
        $headerRow[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headerRow[0]) ?? $headerRow[0];
        $headers = array_map(static fn(string $s): string => strtolower(trim($s)), $headerRow);
        $labelIdx = array_search('nom_fournisseur', $headers, true);
        if ($labelIdx === false) {
            fclose($handle);
            throw new RuntimeException('Colonne nom_fournisseur introuvable.');
        }

        $set = [];
        $count = 0;
        $progress = $io->createProgressBar();
        $progress->setFormat(' %current% lignes scann\xC3\xA9es [%bar%] %elapsed%');
        $progress->start();
        while (($row = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
            $count++;
            if ($count % 10000 === 0) {
                $progress->setProgress($count);
            }
            $label = trim((string) ($row[$labelIdx] ?? ''));
            if ($label === '') {
                continue;
            }
            $norm = ProposeProvidersMapping::normalizeLabel($label);
            if ($norm !== '') {
                $set[$norm] = true;
            }
        }
        $progress->finish();
        $io->newLine(2);
        fclose($handle);
        return $set;
    }

    // =====================================================================
    // Pass 2 CSV — scoring Jaccard
    // =====================================================================

    /**
     * @param list<array{provider_code:int, provider_label:string, normalized:string,
     *                   tokens:list<string>, token_set:array<string,int>}> $unmatched
     * @param array<string, list<int>> $tokenIndex
     * @param array<string, float> $tokenIdf  poids IDF par token
     * @param array<int, list<array{0:float,1:string,2:string}>> $topPerBdd
     */
    private function scoreCsvAgainstUnmatched(
        string $csvPath,
        string $delimiter,
        array $unmatched,
        array $tokenIndex,
        array $tokenIdf,
        int $topN,
        float $minScore,
        array &$topPerBdd,
        SymfonyStyle $io
    ): void {
        $handle = fopen($csvPath, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Ouverture impossible : ' . $csvPath);
        }
        $headerRow = fgetcsv($handle, 0, $delimiter, '"', '\\');
        $headerRow[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headerRow[0]) ?? $headerRow[0];
        $headers = array_map(static fn(string $s): string => strtolower(trim($s)), $headerRow);
        $labelIdx = array_search('nom_fournisseur', $headers, true);
        // Indices des colonnes fiscales (peuvent être absentes : on est tolérant)
        $sirenIdx = array_search('siren', $headers, true);
        $tvaIdx = array_search('numero_tva', $headers, true);
        $nafIdx = array_search('code_naf_etablissement', $headers, true);

        $seenNorm = [];
        $progress = $io->createProgressBar();
        $progress->setFormat(' %current% lignes scor\xC3\xA9es [%bar%] %elapsed%');
        $progress->start();
        $count = 0;
        while (($row = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
            $count++;
            if ($count % 10000 === 0) {
                $progress->setProgress($count);
            }
            $label = trim((string) ($row[$labelIdx] ?? ''));
            if ($label === '') {
                continue;
            }
            $norm = ProposeProvidersMapping::normalizeLabel($label);
            if ($norm === '' || isset($seenNorm[$norm])) {
                continue;
            }
            $seenNorm[$norm] = true;

            $tokens = $this->tokenize($norm);
            $tokenSetCsv = array_flip($tokens);

            $candidates = [];
            foreach ($tokens as $t) {
                if (isset($tokenIndex[$t])) {
                    foreach ($tokenIndex[$t] as $bddIdx) {
                        $candidates[$bddIdx] = true;
                    }
                }
            }

            // Capture les données fiscales du candidat CSV courant
            $csvSiren = $sirenIdx !== false ? trim((string) ($row[$sirenIdx] ?? '')) : '';
            $csvTva   = $tvaIdx !== false ? trim((string) ($row[$tvaIdx] ?? '')) : '';
            $csvNaf   = $nafIdx !== false ? trim((string) ($row[$nafIdx] ?? '')) : '';

            foreach ($candidates as $bddIdx => $_) {
                $score = $this->jaccardWeighted($tokenSetCsv, $unmatched[$bddIdx]['token_set'], $tokenIdf);
                if ($score < $minScore) {
                    continue;
                }
                $this->updateTopN($topPerBdd, $bddIdx, $topN, $score, $label, $norm, $csvSiren, $csvTva, $csvNaf);
            }
        }
        $progress->finish();
        $io->newLine(2);
        fclose($handle);
    }

    /**
     * Stocke un candidat dans le top-N de la BDD donnée. Chaque entrée du
     * top contient [score, label CSV, normalized CSV, siren, tva, naf] pour
     * pouvoir directement produire un fichier 04_to_update_from_diagnostic.csv
     * applicable par Phase 2c.
     *
     * @param array<int, list<array{0:float,1:string,2:string,3:string,4:string,5:string}>> $topPerBdd
     */
    private function updateTopN(
        array &$topPerBdd,
        int $bddIdx,
        int $topN,
        float $score,
        string $label,
        string $norm,
        string $siren = '',
        string $tva = '',
        string $naf = ''
    ): void {
        $entry = [$score, $label, $norm, $siren, $tva, $naf];
        $list = $topPerBdd[$bddIdx] ?? [];
        if (count($list) < $topN) {
            $list[] = $entry;
        } else {
            $minIdx = 0;
            $minScore = $list[0][0];
            for ($i = 1, $n = count($list); $i < $n; $i++) {
                if ($list[$i][0] < $minScore) {
                    $minScore = $list[$i][0];
                    $minIdx = $i;
                }
            }
            if ($score <= $minScore) {
                return;
            }
            $list[$minIdx] = $entry;
        }
        usort($list, static fn(array $a, array $b): int => $b[0] <=> $a[0]);
        $topPerBdd[$bddIdx] = $list;
    }

    // =====================================================================
    // Tokenize + Jaccard
    // =====================================================================

    /**
     * @return list<string>
     */
    private function tokenize(string $normalizedLabel): array
    {
        if ($normalizedLabel === '') {
            return [];
        }
        return array_values(array_filter(
            explode(' ', $normalizedLabel),
            static fn(string $s): bool => $s !== ''
        ));
    }

    /**
     * Calcule l'IDF (Inverse Document Frequency) de chaque token, à partir
     * du set des labels CSV normalisés.
     *
     * IDF(t) = log( (N + 1) / (df(t) + 1) )  — Laplace smoothing pour éviter
     * la division par zéro et borner les tokens absents.
     *
     * Plus un token est rare dans le CSV, plus son IDF est élevé → il pèse
     * plus dans la mesure de similarité. À l'inverse, un mot très courant
     * ("consulting", "france", "services") aura un IDF faible.
     *
     * @param array<string, true> $csvNormalizedSet
     * @return array<string, float>
     */
    private function computeTokenIdf(array $csvNormalizedSet): array
    {
        $n = count($csvNormalizedSet);
        $tokenDf = [];
        foreach ($csvNormalizedSet as $norm => $_) {
            $tokens = array_unique(explode(' ', (string) $norm));
            foreach ($tokens as $t) {
                if ($t === '') {
                    continue;
                }
                $tokenDf[$t] = ($tokenDf[$t] ?? 0) + 1;
            }
        }
        $idf = [];
        foreach ($tokenDf as $token => $df) {
            $idf[$token] = log(($n + 1) / ($df + 1));
        }
        return $idf;
    }

    /**
     * Jaccard pondéré par IDF :
     *   somme(IDF des tokens en intersection) / somme(IDF des tokens en union)
     *
     * Un token absent de la table IDF (peut arriver si un token BDD n'apparaît
     * jamais dans le CSV) reçoit un poids par défaut élevé via Laplace
     * smoothing : log(N+1) au lieu de log((N+1)/(df+1)) — implicitement avec
     * df=0 mais qu'on calcule comme un seuil max.
     *
     * @param array<string, int> $setA
     * @param array<string, int> $setB
     * @param array<string, float> $idf
     */
    private function jaccardWeighted(array $setA, array $setB, array $idf): float
    {
        if ($setA === [] || $setB === []) {
            return 0.0;
        }
        // Poids par défaut pour token inconnu (= très rare) : on prend le max
        // observé dans la table IDF + une marge légère. Acceptable en RAM
        // (recalculé une fois mais mémorisé dans un static).
        static $defaultWeight = null;
        if ($defaultWeight === null) {
            $defaultWeight = $idf === [] ? 1.0 : max($idf);
        }

        $interW = 0.0;
        $unionW = 0.0;
        foreach ($setA as $token => $_) {
            $w = $idf[$token] ?? $defaultWeight;
            $unionW += $w;
            if (isset($setB[$token])) {
                $interW += $w;
            }
        }
        foreach ($setB as $token => $_) {
            if (!isset($setA[$token])) {
                $unionW += $idf[$token] ?? $defaultWeight;
            }
        }
        return $unionW > 0.0 ? $interW / $unionW : 0.0;
    }

    // =====================================================================
    // Sortie
    // =====================================================================

    /**
     * @param list<array{provider_code:int, provider_label:string, normalized:string}> $unmatched
     * @param array<int, list<array{0:float,1:string,2:string}>> $topPerBdd
     */
    private function writeReport(string $outPath, array $unmatched, array $topPerBdd, int $topN, string $delimiter): void
    {
        $h = fopen($outPath, 'wb');
        if ($h === false) {
            throw new RuntimeException('Écriture impossible : ' . $outPath);
        }

        $header = ['provider_code', 'provider_label_bdd', 'normalized_bdd'];
        for ($i = 1; $i <= $topN; $i++) {
            $header[] = "candidate_{$i}_label";
            $header[] = "candidate_{$i}_score";
        }
        fputcsv($h, $header, $delimiter);

        // Tri par best score DESC : les loupés les plus probables en haut
        $rows = [];
        foreach ($unmatched as $idx => $bddRow) {
            $top = $topPerBdd[$idx] ?? [];
            $bestScore = $top !== [] ? $top[0][0] : -1.0;
            $rows[] = [$bestScore, $bddRow, $top];
        }
        usort($rows, static fn(array $a, array $b): int => $b[0] <=> $a[0]);

        foreach ($rows as [$_, $bddRow, $top]) {
            $line = [
                (string) $bddRow['provider_code'],
                $bddRow['provider_label'],
                $bddRow['normalized'],
            ];
            for ($i = 0; $i < $topN; $i++) {
                if (isset($top[$i])) {
                    $line[] = (string) $top[$i][1];
                    $line[] = number_format($top[$i][0], 3);
                } else {
                    $line[] = '';
                    $line[] = '';
                }
            }
            fputcsv($h, $line, $delimiter);
        }
        fclose($h);
    }

    // =====================================================================
    // Helpers
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
}
