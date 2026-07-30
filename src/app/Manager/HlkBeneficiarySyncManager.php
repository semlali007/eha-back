<?php

namespace App\Manager;

use App\Models\Beneficiary;
use App\Models\Sheet;
use App\Models\SheetBeneficiary;
use App\Services\HlkSheetsService;
use Core\Database\DBConnection;
use Throwable;

/**
 * Synchronise les bénéficiaires des fiches existantes depuis HLK.
 *
 * Déclenchée à la demande (bouton admin), cette classe :
 *   1. Streame HLK page par page (générateur — chaque page reçue est traitée
 *      immédiatement, ce qui préserve la progression si HLK crash en cours)
 *   2. Pour chaque row : cherche la ou les fiches EHA qui référencent
 *      contrat_numero via sheet_contracts
 *   3. Si trouvée(s) : ajoute contrat_beneficiaire à sheet_filiales (type=beneficiary)
 *      — création à la volée dans la table filiale si le nom est inconnu
 *   4. Ne touche à AUCUN autre champ : pas de suppression, pas de MAJ d'autre chose
 *
 * Retourne un rapport structuré pour affichage dans l'écran admin. Si HLK
 * timeout en cours de stream, on remonte l'erreur ET les stats partielles
 * (les rows déjà traitées ne sont pas perdues).
 */
class HlkBeneficiarySyncManager
{
    /** @var array<string,int> Cache : nom → filiale.id (évite les lookups répétés) */
    private array $beneficiaryCache = [];

    /** @var array<string,array<int,array{id:int,label:string}>> Cache : contract_code → fiches trouvées */
    private array $contractCache = [];

    /**
     * Détail par contrat HLK : liste dédupée des bénéficiaires renvoyés par HLK
     * pour chaque contract_code, avec les fiches EHA matchées. Sert au rapport
     * final pour permettre au métier de vérifier que HLK renvoie bien tous les
     * bénéficiaires attendus (comparaison avec le contrat papier).
     *
     * Structure : contract_code → [ 'benefs' => [orig_name => true, ...],
     *                               'sheets' => [sheet_id => label, ...] ]
     *
     * @var array<string,array{benefs:array<string,bool>,sheets:array<int,string>}>
     */
    private array $contractDetails = [];

    /**
     * Cache : sheet_id → 2 sets de noms normalisés (lower+unaccent+trim).
     *
     * Structure de chaque entrée :
     *   [
     *     'beneficiary' => [ 'norm_name1' => true, 'norm_name2' => true, ... ],
     *     'blocking'    => [ 'norm_name3' => true, ... ]
     *   ]
     *
     * - 'beneficiary' : noms des rows sheet_filiales de type='beneficiary'
     * - 'blocking'    : noms des rows sheet_filiales d'AUTRE type (stipulation…)
     *
     * La comparaison "déjà rattaché ?" se fait par nom normalisé, ce qui tolère
     * les doublons historiques dans filiale (Enedis id=3 et ENEDIS id=2400).
     * Le set 'blocking' évite les tentatives d'INSERT vouées à l'échec quand
     * la même paire (sheet, filiale) existe déjà sous un autre type — la
     * contrainte UNIQUE(sheet_id, filiale_id) empêche la co-existence.
     *
     * @var array<int, array<string, array<string, bool>>>
     */
    private array $existingLinksCache = [];

    /**
     * @param bool $dryRun Si true : aucune écriture BDD, on retourne juste le rapport
     *   de ce qui SERAIT fait + un aperçu (preview) pour permettre à l'admin
     *   de vérifier avant de valider. Aucune filiale n'est créée, aucun lien
     *   sheet_filiales n'est posé. Sûr à lancer en preprod / prod.
     *
     * @param callable|null $onProgress Callback appelé après chaque page traitée
     *   avec (int $pageNum, array $partialStats). Utilisé par le controller pour
     *   émettre des octets keepalive et éviter les timeouts intermédiaires
     *   (nginx / WAF EDF) sur ce traitement long (~5-6 min).
     *
     * @return array Rapport structuré : dry_run, pages_processed,
     *   rows_fetched, contracts_processed, contracts_matched, contracts_unmatched,
     *   fiches_updated, links_created, beneficiaries_created, errors,
     *   duration_ms, started_at, finished_at, fetch_info, coverage,
     *   warnings, preview (uniquement quand dry_run=true)
     */
    public function run(bool $dryRun = false, ?callable $onProgress = null): array
    {
        $started = new \DateTimeImmutable();
        $t0 = microtime(true);

        $stats = [
            'dry_run'                          => $dryRun,
            'pages_processed'                  => 0,
            'rows_fetched'                     => 0,
            'rows_skipped_not_open_to_filiales'=> 0, // rows HLK filtrées : contrat pas ouvert aux filiales
            'contracts_processed'              => 0, // nb de (contract_code, beneficiaire) traités
            'contracts_matched'                => 0, // avec au moins 1 fiche EHA trouvée
            'contracts_unmatched'              => 0, // contrats HLK non présents en EHA (log info)
            'fiches_updated'                   => 0, // fiches distinctes ayant reçu au moins 1 lien
            'links_created'                    => 0, // nouvelles lignes sheet_filiales
            'beneficiaries_created'            => 0, // filiales créées en base (ou qui seraient créées)
            'errors'                           => [],
            'warnings'                         => [],
        ];

        // Preview : uniquement rempli en dry_run — permet à l'admin de vérifier
        // ce qui sera fait avant de valider.
        $preview = [
            'new_beneficiaries' => [],     // set de labels bénéficiaires qui seraient créés
            'fiches_by_id'      => [],     // sheet_id → { label, contracts:set, beneficiaries:set }
            'unmatched_samples' => [],     // premiers ~30 contract_codes HLK sans fiche en EHA
        ];
        $unmatchedSet = [];                // pour dédup des unmatched

        error_log('[hlk-benef-sync] ================================================================');
        error_log('[hlk-benef-sync] Démarrage sync bénéficiaires ' . ($dryRun ? '[DRY-RUN]' : '[REAL]'));

        $sheet         = new Sheet();
        $sheetBenef    = new SheetBeneficiary();
        $benefModel    = new Beneficiary();
        $updatedSheets = []; // set des sheet_id modifiés
        $rowIndexGlobal = 0;

        $service = new HlkSheetsService();
        try {
            // Streaming page par page : on traite les rows dès qu'une page arrive.
            // Si HLK meurt à la page N, tout ce qui a été traité avant est déjà
            // écrit / compté (les stats sont à jour, et le catch remonte les
            // stats partielles avec l'erreur).
            foreach ($service->streamAllPages() as $pageNum => $pageRows) {
                $stats['pages_processed']++;
                $stats['rows_fetched'] += count($pageRows);

                error_log(sprintf(
                    '[hlk-benef-sync] >>> Traitement page %d (%d rows, cumul %d)',
                    $pageNum, count($pageRows), $stats['rows_fetched']
                ));
                $tPage = microtime(true);

                foreach ($pageRows as $row) {
                    $rowIndexGlobal++;
                    $this->processRow(
                        $row, $rowIndexGlobal, $pageNum,
                        $sheet, $sheetBenef, $benefModel,
                        $stats, $preview, $unmatchedSet, $updatedSheets,
                        $dryRun
                    );
                }

                $pageMs = round((microtime(true) - $tPage) * 1000);
                error_log(sprintf(
                    '[hlk-benef-sync] <<< Page %d traitée en %dms | matched=%d unmatched=%d | fiches_updated=%d links=%d benefs=%d',
                    $pageNum, $pageMs,
                    $stats['contracts_matched'], $stats['contracts_unmatched'],
                    count($updatedSheets), $stats['links_created'], $stats['beneficiaries_created']
                ));

                // Keepalive : appelle le callback pour émettre des octets vers
                // le client et éviter les timeouts nginx / WAF sur cette
                // requête longue durée. On protège l'appel : si le client a
                // fermé la connexion en cours de sync, flush() peut throw —
                // on ne veut pas perdre les pages restantes pour autant.
                if ($onProgress !== null) {
                    try {
                        $onProgress($pageNum, $stats);
                    } catch (Throwable $e) {
                        error_log("[hlk-benef-sync] ⚠ onProgress callback threw : " . $e->getMessage());
                    }
                }
            }

            $stats['fiches_updated'] = count($updatedSheets);

        } catch (Throwable $e) {
            // Erreur en cours de stream (HLK timeout p.ex.) : on garde les stats
            // partielles pour que le front voie quand même ce qu'on a réussi
            // à traiter avant.
            $stats['fiches_updated'] = count($updatedSheets);
            $stats['errors'][] = [
                'level'   => 'fatal',
                'message' => $e->getMessage(),
                'after_page' => $stats['pages_processed'],
                'after_row'  => $rowIndexGlobal,
            ];
            error_log(sprintf(
                '[hlk-benef-sync] ⚠ FATAL après page=%d row=%d : %s',
                $stats['pages_processed'], $rowIndexGlobal, $e->getMessage()
            ));
        }

        // Rapport de fetch HLK (pages skipées, ratio couverture) — même si le
        // stream a levé une exception avant fin, le service a stocké l'info
        // partielle.
        $fetchInfo = $service->getLastFetchInfo();
        if ($fetchInfo !== null) {
            $stats['fetch_info'] = $fetchInfo;

            // Warning explicite si des pages HLK ont été skipées (records
            // corrompus suspectés côté HLK) ou si couverture < 100% pour une
            // autre raison. Le front affiche au métier avec la liste des pages.
            $skipped = $fetchInfo['pages_skipped'] ?? [];
            if (!empty($skipped) || ($fetchInfo['coverage_ratio'] ?? 1.0) < 1.0) {
                $stats['warnings'][] = [
                    'code'    => 'hlk_pages_skipped',
                    'message' => sprintf(
                        "Pages HLK ignorées (records HLK non lisibles) : %d page(s) sur %d [%s]. Les lignes de ces pages ne sont pas synchronisées. Signaler à l'équipe HLK avec ces numéros de pages.",
                        count($skipped),
                        $fetchInfo['total_pages_hlk'],
                        implode(', ', $skipped) ?: '—'
                    ),
                    'pages_skipped'   => $skipped,
                    'total_pages_hlk' => $fetchInfo['total_pages_hlk'],
                    'pages_fetched'   => $fetchInfo['pages_fetched'],
                    'coverage_ratio'  => $fetchInfo['coverage_ratio'],
                ];
            }
        }

        // Rapport de couverture côté EHA : combien de fiches on a en base vs
        // combien ont été touchées par la sync. Permet au métier de juger le
        // résultat sans dépendre uniquement des stats HLK.
        $stats['coverage'] = $this->computeEhaCoverage(count($updatedSheets));

        // Détail par contrat : liste dédupée des bénéficiaires renvoyés par HLK,
        // triée alphabétiquement, avec les fiches EHA matchées. Le métier utilise
        // ce bloc pour vérifier que HLK renvoie bien tous les bénéficiaires
        // attendus (par rapport au contrat papier).
        $contractsOut = [];
        foreach ($this->contractDetails as $code => $detail) {
            $benefs = array_keys($detail['benefs']);
            sort($benefs, SORT_NATURAL | SORT_FLAG_CASE);
            $sheets = [];
            foreach ($detail['sheets'] as $sheetId => $label) {
                $sheets[] = ['id' => $sheetId, 'label' => $label];
            }
            $contractsOut[] = [
                'contract_code'  => $code,
                'nb_beneficiaries' => count($benefs),
                'beneficiaries'  => $benefs,
                'matched_sheets' => $sheets,
                'is_matched'     => !empty($sheets),
            ];
        }
        // Tri : d'abord les matchés (pour les remonter en tête du rapport),
        // puis par contract_code alphabétique.
        usort($contractsOut, static function ($a, $b) {
            if ($a['is_matched'] !== $b['is_matched']) {
                return $a['is_matched'] ? -1 : 1;
            }
            return strcmp($a['contract_code'], $b['contract_code']);
        });
        $stats['contracts_details'] = [
            'total'         => count($contractsOut),
            'matched'       => count(array_filter($contractsOut, static fn($c) => $c['is_matched'])),
            'unmatched'     => count(array_filter($contractsOut, static fn($c) => !$c['is_matched'])),
            'contracts'     => $contractsOut,
        ];

        $finished = new \DateTimeImmutable();
        $stats['duration_ms'] = (int) round((microtime(true) - $t0) * 1000);
        $stats['started_at']  = $started->format(\DateTimeInterface::ATOM);
        $stats['finished_at'] = $finished->format(\DateTimeInterface::ATOM);

        // Détail des fiches touchées + bénéficiaires ajoutés + samples unmatched.
        // Populé dans les DEUX modes :
        //   - dry_run : "aperçu de ce qui SERAIT fait"
        //   - real    : "audit de ce qui a été fait"
        // Le champ `stats.preview` reste le même pour un affichage unifié côté front.
        $fiches = array_map(function ($f) {
            return [
                'id'                      => $f['id'],
                'label'                   => $f['label'],
                'contracts'               => array_keys($f['contracts']),
                'beneficiaries_to_add'    => array_keys($f['beneficiaries']),
                'nb_beneficiaries_to_add' => count($f['beneficiaries']),
            ];
        }, $preview['fiches_by_id']);
        // Trier par nb bénéficiaires décroissant pour surfacer les impacts majeurs
        usort($fiches, fn($a, $b) => $b['nb_beneficiaries_to_add'] <=> $a['nb_beneficiaries_to_add']);

        $stats['preview'] = [
            'new_beneficiaries' => array_keys($preview['new_beneficiaries']),
            // Liste COMPLÈTE des fiches enrichies (plus de limite top 30).
            // Le front doit paginer côté client — les données restent
            // manageable (~qq centaines de fiches × qq bénéfs chacune).
            'fiches'            => $fiches,
            'fiches_total'      => count($fiches),
            // Conservé pour rétrocompat au cas où le front l'utilise :
            'fiches_sample'     => array_slice($fiches, 0, 30),
            'unmatched_samples' => $preview['unmatched_samples'],
        ];

        error_log(sprintf(
            "[hlk-benef-sync] terminé %s en %dms | pages=%d rows=%d skipped_not_open=%d | contracts_processed=%d matched=%d unmatched=%d | fiches_updated=%d links_created=%d benefs_created=%d | coverage=%d/%d (%.1f%%) | errors=%d warnings=%d",
            $dryRun ? '[DRY-RUN]' : '[REAL]',
            $stats['duration_ms'],
            $stats['pages_processed'],
            $stats['rows_fetched'],
            $stats['rows_skipped_not_open_to_filiales'],
            $stats['contracts_processed'],
            $stats['contracts_matched'],
            $stats['contracts_unmatched'],
            $stats['fiches_updated'],
            $stats['links_created'],
            $stats['beneficiaries_created'],
            $stats['coverage']['sheets_touched'] ?? 0,
            $stats['coverage']['eha_sheets_total'] ?? 0,
            ($stats['coverage']['touched_ratio'] ?? 0) * 100,
            count($stats['errors']),
            count($stats['warnings'])
        ));
        error_log('[hlk-benef-sync] ================================================================');

        return $stats;
    }

    /**
     * Traite UNE ligne HLK. Flow métier :
     *
     *   1. Extraire (contract_code, benef_name) de la row HLK
     *   2. Lookup contract → fiche(s) EHA rattachée(s) (cache)
     *   3. Pour chaque fiche trouvée :
     *      a. Charger les bénéficiaires actuellement rattachés à la fiche
     *      b. Le nom HLK est-il déjà rattaché ? (comparaison lower+unaccent+trim)
     *         → OUI : SKIP, rien à faire
     *         → NON : résoudre/créer la row filiale, poser le lien sheet_filiales
     *
     * La résolution `Beneficiary::get()` est PARESSEUSE : on ne fait le lookup
     * que si au moins une fiche a besoin d'un ajout. Si toutes les fiches
     * matchées ont déjà le bénéf, on n'appelle jamais get() → pas de DB roundtrip.
     *
     * Toutes les erreurs row-level sont capturées dans $stats['errors'] pour
     * ne pas casser le loop de la sync.
     */
    private function processRow(
        array $row,
        int $rowIndex,
        int $pageNum,
        Sheet $sheet,
        SheetBeneficiary $sheetBenef,
        Beneficiary $benefModel,
        array &$stats,
        array &$preview,
        array &$unmatchedSet,
        array &$updatedSheets,
        bool $dryRun
    ): void {
        try {
            // === 0. Filtre métier : seuls les contrats OUVERTS AUX FILIALES ===
            //         Le champ `contrat_ouvert_filiales` est toujours envoyé par
            //         HLK ; null → considéré non-ouvert → skip.
            if (!$this->isOpenToFiliales($row['contrat_ouvert_filiales'] ?? null)) {
                $stats['rows_skipped_not_open_to_filiales']++;
                return;
            }

            // === 1. Extraire (contract, benef) de la row HLK ==================
            $contractCode    = trim((string) ($row['contrat_numero']       ?? ''));
            $beneficiaryName = $this->cleanText($row['contrat_beneficiaire'] ?? '');

            if ($contractCode === '' || $beneficiaryName === '') {
                error_log(sprintf(
                    '[hlk-benef-sync] row #%d (p%d) SKIP : contract=%s benef=%s',
                    $rowIndex, $pageNum,
                    $contractCode === '' ? '(vide)' : $contractCode,
                    $beneficiaryName === '' ? '(vide)' : "'{$beneficiaryName}'"
                ));
                return;
            }

            $stats['contracts_processed']++;
            $normBenefKey = $this->normalizeKey($beneficiaryName);

            // Alimenter le détail par contrat pour le rapport final
            // (permet au métier de vérifier que HLK renvoie bien la liste
            // complète des bénéficiaires attendus).
            if (!isset($this->contractDetails[$contractCode])) {
                $this->contractDetails[$contractCode] = ['benefs' => [], 'sheets' => []];
            }
            $this->contractDetails[$contractCode]['benefs'][$beneficiaryName] = true;

            // === 2. Lookup contract → fiches (cache) ==========================
            if (!isset($this->contractCache[$contractCode])) {
                $tLookup = microtime(true);
                $found = $sheet->getByContract($contractCode);
                $rows_ = [];
                foreach ($found ?? [] as $r) {
                    $rows_[] = ['id' => (int) $r['id'], 'label' => $r['label'] ?? ''];
                }
                $this->contractCache[$contractCode] = $rows_;
                $lookupMs = round((microtime(true) - $tLookup) * 1000);
                error_log(sprintf(
                    '[hlk-benef-sync] row #%d (p%d) LOOKUP contract=%s → %d fiche(s) (%dms) [cache miss]',
                    $rowIndex, $pageNum, $contractCode, count($rows_), $lookupMs
                ));
            }
            $matchedSheets = $this->contractCache[$contractCode];

            if (empty($matchedSheets)) {
                $stats['contracts_unmatched']++;
                error_log(sprintf(
                    '[hlk-benef-sync] row #%d (p%d) UNMATCHED contract=%s benef=\'%s\'',
                    $rowIndex, $pageNum, $contractCode, $beneficiaryName
                ));
                if (!isset($unmatchedSet[$contractCode]) && count($preview['unmatched_samples']) < 30) {
                    $preview['unmatched_samples'][] = $contractCode;
                    $unmatchedSet[$contractCode] = true;
                }
                return;
            }
            $stats['contracts_matched']++;

            // Enregistrer les fiches matchées pour le détail par contrat
            foreach ($matchedSheets as $matched) {
                $this->contractDetails[$contractCode]['sheets'][(int) $matched['id']] = (string) $matched['label'];
            }

            // === 3. Pour chaque fiche matchée : skip si le nom est déjà rattaché
            //         en 'beneficiary' (rien à faire), skip proprement avec warning
            //         si déjà rattaché en 'stipulation' (INSERT échouerait), sinon
            //         résoudre la filiale (paresseux) et ajouter le lien.
            $filialeId       = null; // résolu à la demande, une seule fois
            $addedSheetIds   = [];
            $skippedSheetIds = [];
            $blockedSheetIds = [];

            foreach ($matchedSheets as $matched) {
                $sheetId = $matched['id'];
                $label   = $matched['label'];

                // 3a. Charger les 2 sets de noms pour cette fiche (memoïsé) :
                //     'beneficiary' → déjà rattaché comme bénéficiaire
                //     'blocking'    → déjà rattaché en autre type (stipulation…)
                $existing    = $this->getExistingLinks($sheetId);
                $benefSet    = $existing['beneficiary'];
                $blockingSet = $existing['blocking'];

                // 3b. Déjà lié en 'beneficiary' → skip silencieux
                if (isset($benefSet[$normBenefKey])) {
                    $skippedSheetIds[] = $sheetId;
                    continue;
                }

                // 3c. Déjà lié en AUTRE type (stipulation) → l'INSERT échouerait
                //     car la contrainte UNIQUE(sheet_id, filiale_id) n'inclut pas
                //     le type. On skip proprement + on marque le cache pour ne
                //     pas retenter sur les rows HLK dupliquées → coupe la boucle.
                if (isset($blockingSet[$normBenefKey])) {
                    $this->existingLinksCache[$sheetId]['beneficiary'][$normBenefKey] = true;
                    $blockedSheetIds[] = $sheetId;
                    $stats['warnings'][] = [
                        'code'          => 'blocked_by_other_type',
                        'sheet_id'      => $sheetId,
                        'sheet_label'   => $label,
                        'contract_code' => $contractCode,
                        'beneficiary'   => $beneficiaryName,
                        'message'       => sprintf(
                            "Fiche %d : '%s' déjà rattaché en 'stipulation pour autrui' — impossible de l'ajouter aussi en bénéficiaire (contrainte BDD sheet_filiales). À traiter manuellement si besoin.",
                            $sheetId, $beneficiaryName
                        ),
                    ];
                    error_log(sprintf(
                        "[hlk-benef-sync] row #%d (p%d) BLOCKED_BY_STIPULATION sheet_id=%d benef='%s' — skip",
                        $rowIndex, $pageNum, $sheetId, $beneficiaryName
                    ));
                    continue;
                }

                // 3d. Résolution paresseuse — au 1er ajout nécessaire seulement
                if ($filialeId === null) {
                    $filialeId = $this->resolveOrCreateBeneficiary(
                        $beneficiaryName, $benefModel, $stats, $dryRun, $preview
                    );
                    if ($filialeId === null) {
                        error_log(sprintf(
                            '[hlk-benef-sync] row #%d (p%d) BENEF_NULL contract=%s benef=\'%s\', abandon row',
                            $rowIndex, $pageNum, $contractCode, $beneficiaryName
                        ));
                        return; // impossible de résoudre → on abandonne toute la row
                    }
                }

                // 3e. Ajouter le lien (skip write en dry_run)
                try {
                    if (!$dryRun) {
                        $sheetBenef->add($sheetId, $filialeId);
                    }
                    // Marquer le NOM comme désormais rattaché à cette fiche pour
                    // dédupliquer les rows HLK suivantes ayant la même paire.
                    $this->existingLinksCache[$sheetId]['beneficiary'][$normBenefKey] = true;

                    $stats['links_created']++;
                    $updatedSheets[$sheetId] = true;
                    $addedSheetIds[] = $sheetId;

                    // Agrégation par fiche — populée dans les 2 modes :
                    //   - dry_run : liste des ajouts qui SERAIENT faits (preview)
                    //   - real    : audit des ajouts EFFECTUÉS
                    if (!isset($preview['fiches_by_id'][$sheetId])) {
                        $preview['fiches_by_id'][$sheetId] = [
                            'id'            => $sheetId,
                            'label'         => $label,
                            'contracts'     => [],
                            'beneficiaries' => [],
                        ];
                    }
                    $preview['fiches_by_id'][$sheetId]['contracts'][$contractCode]       = true;
                    $preview['fiches_by_id'][$sheetId]['beneficiaries'][$beneficiaryName] = true;
                } catch (Throwable $e) {
                    // Marquer quand même le cache pour éviter le retry sur les
                    // rows HLK dupliquées (128 erreurs identiques → une seule).
                    $this->existingLinksCache[$sheetId]['beneficiary'][$normBenefKey] = true;
                    $stats['errors'][] = [
                        'contract_code' => $contractCode,
                        'beneficiary'   => $beneficiaryName,
                        'sheet_id'      => $sheetId,
                        'message'       => $e->getMessage(),
                    ];
                    error_log("[hlk-benef-sync] ⚠ row #{$rowIndex} échec add sheet_id={$sheetId} filiale_id={$filialeId} : " . $e->getMessage());
                }
            }

            error_log(sprintf(
                '[hlk-benef-sync] row #%d (p%d) contract=%s benef=\'%s\' → added=[%s] skipped_already_linked=[%s] blocked_by_stipulation=[%s]',
                $rowIndex, $pageNum, $contractCode, $beneficiaryName,
                implode(',', $addedSheetIds),
                implode(',', $skippedSheetIds),
                implode(',', $blockedSheetIds)
            ));
        } catch (Throwable $e) {
            $stats['errors'][] = [
                'row_index' => $rowIndex,
                'message'   => $e->getMessage(),
            ];
            error_log("[hlk-benef-sync] ⚠ erreur row #{$rowIndex} (p{$pageNum}) : " . $e->getMessage());
        }
    }

    /**
     * Charge (une seule fois par fiche, memoïsé) 2 sets de noms normalisés :
     *   - 'beneficiary' : noms déjà liés à la fiche en type='beneficiary'
     *                     → skip silencieux (rien à faire)
     *   - 'blocking'    : noms déjà liés à la fiche en AUTRE type (ex. 'stipulation')
     *                     → skip avec warning (l'INSERT échouerait à cause de la
     *                       contrainte UNIQUE(sheet_id, filiale_id) qui n'inclut
     *                       pas le type)
     *
     * La lecture par type conserve la sémantique demandée : la vérif "est-ce
     * qu'il est déjà là comme bénéficiaire ?" reste filtrée sur beneficiary.
     * Le set 'blocking' sert uniquement à couper court au retry inutile ET
     * à produire un message d'erreur clair dans le rapport.
     *
     * Fait la requête directement (pas via SheetBeneficiary::getBySheet) pour
     * récupérer les 2 types en un seul roundtrip DB.
     *
     * @return array<string, array<string, bool>>  ['beneficiary' => [...], 'blocking' => [...]]
     */
    private function getExistingLinks(int $sheetId): array
    {
        if (isset($this->existingLinksCache[$sheetId])) {
            return $this->existingLinksCache[$sheetId];
        }
        $beneficiary = [];
        $blocking    = [];
        try {
            $db     = DBConnection::getInstance();
            $schema = $db->getSchema(); // ex: "EHA".
            $sql = "SELECT f.name, sf.type
                    FROM {$schema}sheet_filiales sf
                    INNER JOIN {$schema}filiale f ON f.id = sf.filiale_id
                    WHERE sf.sheet_id = :sheet_id";
            $sth = $db->getPDO()->prepare($sql);
            $sth->bindValue(':sheet_id', $sheetId, \PDO::PARAM_INT);
            $sth->execute();
            foreach ($sth->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                if (empty($row['name'])) continue;
                $key = $this->normalizeKey((string) $row['name']);
                if (($row['type'] ?? '') === 'beneficiary') {
                    $beneficiary[$key] = true;
                } else {
                    $blocking[$key] = true;
                }
            }
        } catch (Throwable $e) {
            error_log("[hlk-benef-sync] ⚠ getExistingLinks sheet_id={$sheetId} échec : " . $e->getMessage());
        }
        $this->existingLinksCache[$sheetId] = ['beneficiary' => $beneficiary, 'blocking' => $blocking];
        return $this->existingLinksCache[$sheetId];
    }

    /**
     * Cherche un bénéficiaire par nom exact ; crée s'il n'existe pas.
     *
     * En dry_run : lookup normal ; si absent → on ne crée PAS, on note dans le
     * preview et on retourne un ID "virtuel" (0) pour que le flux continue
     * (le compteur links_created s'incrémente comme en mode réel).
     *
     * @param bool  $dryRun
     * @param array &$preview Preview du dry_run (set des labels à créer)
     */
    private function resolveOrCreateBeneficiary(
        string $name,
        Beneficiary $model,
        array &$stats,
        bool $dryRun,
        array &$preview
    ): ?int {
        // Clé cache normalisée comme le SQL de Beneficiary::get :
        // lower + trim + strip accents. Sans strip d'accents ici, "Electricité"
        // et "ELECTRICITE" auraient 2 cache keys distincts → 2 lookups + double
        // comptage de beneficiaries_created en dry-run.
        $key = $this->normalizeKey($name);
        if (isset($this->beneficiaryCache[$key])) {
            return $this->beneficiaryCache[$key];
        }
        try {
            $found = $model->get(['name' => $name]);
            if (is_array($found) && count($found) > 0 && isset($found[0]['id'])) {
                $this->beneficiaryCache[$key] = (int) $found[0]['id'];
                return $this->beneficiaryCache[$key];
            }
            // Le bénéficiaire n'existe pas.
            if ($dryRun) {
                // On note ce qui serait créé, sans écrire.
                $preview['new_beneficiaries'][$name] = true;
                $stats['beneficiaries_created']++;
                error_log("[hlk-benef-sync] filiale '{$name}' À CRÉER (dry-run)");
                // ID virtuel 0 pour permettre au flux de continuer et compter les links
                $this->beneficiaryCache[$key] = 0;
                return 0;
            }
            // Création réelle (ON CONFLICT DO NOTHING — retour 0 si conflit)
            $newId = (int) $model->add(['name' => $name]);
            if ($newId > 0) {
                $this->beneficiaryCache[$key] = $newId;
                $stats['beneficiaries_created']++;
                // Audit : liste des filiales réellement créées (même clé que
                // le preview dry-run pour un affichage unifié côté front).
                $preview['new_beneficiaries'][$name] = true;
                error_log("[hlk-benef-sync] filiale '{$name}' CRÉÉE (id={$newId})");
                return $newId;
            }
            // Fallback : re-lookup si ON CONFLICT n'a rien retourné
            $found = $model->get(['name' => $name]);
            if (is_array($found) && count($found) > 0 && isset($found[0]['id'])) {
                $this->beneficiaryCache[$key] = (int) $found[0]['id'];
                return $this->beneficiaryCache[$key];
            }
        } catch (Throwable $e) {
            $stats['errors'][] = [
                'beneficiary' => $name,
                'message'     => 'resolveOrCreateBeneficiary: ' . $e->getMessage(),
            ];
            error_log("[hlk-benef-sync] ⚠ resolve filiale '{$name}' échec : " . $e->getMessage());
        }
        return null;
    }

    /**
     * Rapport de couverture EHA : combien de fiches en base, combien touchées.
     * Le métier voit d'un coup si la sync a atteint une part significative
     * du référentiel EHA. Un ratio très bas est un signal qu'il faut creuser
     * côté HLK (pages skipées probablement).
     *
     * @param int $touchedCount Nombre de fiches touchées par cette sync
     * @return array{
     *   eha_sheets_total:int,
     *   sheets_touched:int,
     *   touched_ratio:float
     * }
     */
    private function computeEhaCoverage(int $touchedCount): array
    {
        $total = 0;
        try {
            $db     = DBConnection::getInstance();
            $schema = $db->getSchema(); // ex: "EHA".
            $stmt   = $db->getPDO()->query("SELECT COUNT(*) AS c FROM {$schema}sheet_headers");
            $row    = $stmt->fetch(\PDO::FETCH_ASSOC);
            $total  = (int) ($row['c'] ?? 0);
        } catch (Throwable $e) {
            error_log('[hlk-benef-sync] ⚠ coverage : COUNT(sheet_headers) échec : ' . $e->getMessage());
        }
        return [
            'eha_sheets_total' => $total,
            'sheets_touched'   => $touchedCount,
            'touched_ratio'    => $total > 0 ? round($touchedCount / $total, 3) : 0.0,
        ];
    }

    /**
     * Normalise un nom pour la clé de cache in-memory. Doit refléter exactement
     * ce que Beneficiary::get() compare côté SQL : LOWER(unaccent(TRIM(...))).
     *
     * ⚠️ Ne PAS utiliser iconv//TRANSLIT ici : le résultat dépend de la locale
     * système et peut retourner '?' ou '' pour un accent sur des serveurs
     * mal configurés (RHEL 7 default, conteneurs minimaux). Résultat :
     * 'Electricité' → 'electricit?' côté PHP mais 'electricite' côté SQL → mismatch.
     *
     * Approche en 2 couches déterministes :
     *   1. Normalizer::FORM_D + strip \p{Mn} — méthode standard, gère toutes
     *      les combinaisons Unicode. Requiert ext-intl (présent par défaut PHP 8+).
     *   2. Table de fallback explicite — attrape les ligatures (œ, æ, ß) que
     *      Normalizer ne décompose pas, et couvre le cas où intl est absent.
     */
    private function normalizeKey(string $name): string
    {
        $s = trim($name);
        if ($s === '') return '';

        // Couche 1 : décomposition Unicode + strip des marques combinantes
        //   'é' (U+00E9)  →  'e' + U+0301  →  strip U+0301  →  'e'
        if (class_exists('\Normalizer')) {
            $decomposed = @\Normalizer::normalize($s, \Normalizer::FORM_D);
            if (is_string($decomposed)) {
                $stripped = preg_replace('/\p{Mn}+/u', '', $decomposed);
                if (is_string($stripped)) {
                    $s = $stripped;
                }
            }
        }

        // Couche 2 : table de correspondance pour :
        //   - Ligatures / caractères composés non décomposés par NFD (œ, æ, ß)
        //   - Fallback complet si ext-intl indisponible ou Normalizer a échoué
        static $map = [
            'À'=>'A','Á'=>'A','Â'=>'A','Ã'=>'A','Ä'=>'A','Å'=>'A',
            'à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a','å'=>'a',
            'Ç'=>'C','ç'=>'c',
            'È'=>'E','É'=>'E','Ê'=>'E','Ë'=>'E',
            'è'=>'e','é'=>'e','ê'=>'e','ë'=>'e',
            'Ì'=>'I','Í'=>'I','Î'=>'I','Ï'=>'I',
            'ì'=>'i','í'=>'i','î'=>'i','ï'=>'i',
            'Ñ'=>'N','ñ'=>'n',
            'Ò'=>'O','Ó'=>'O','Ô'=>'O','Õ'=>'O','Ö'=>'O','Ø'=>'O',
            'ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o','ø'=>'o',
            'Ù'=>'U','Ú'=>'U','Û'=>'U','Ü'=>'U',
            'ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u',
            'Ý'=>'Y','ý'=>'y','ÿ'=>'y',
            'Œ'=>'OE','œ'=>'oe',
            'Æ'=>'AE','æ'=>'ae',
            'ß'=>'ss',
        ];
        $s = strtr($s, $map);

        return mb_strtolower($s, 'UTF-8');
    }

    /**
     * True si la valeur `contrat_ouvert_filiales` renvoyée par HLK indique OUI.
     *
     * Règle métier (confirmée) : HLK renvoie toujours le champ, mais peut être
     * null ; null est considéré comme false (contrat pas ouvert aux filiales →
     * skip). Idem pour toute autre valeur "négative" (false, 0, 'false', 'non', '').
     *
     * Tolère les représentations positives : booléen true, entier 1, chaînes
     * 'true'/'1'/'oui'/'yes' (insensible casse) — au cas où HLK sérialise
     * différemment selon la version.
     */
    private function isOpenToFiliales(mixed $value): bool
    {
        if ($value === null)  return false;
        if ($value === true)  return true;
        if ($value === 1)     return true;
        if (is_string($value)) {
            $v = strtolower(trim($value));
            return in_array($v, ['true', '1', 'oui', 'yes'], true);
        }
        return false;
    }

    /**
     * Corrige le double-encodage UTF-8 fréquent dans le payload HLK (MarchÃ© → Marché)
     * et trim les blancs. Idempotent : sans souci sur les chaînes déjà propres.
     */
    private function cleanText(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (preg_match('/Ã[©¨ª«®¯»¸¡Â]/u', $value)) {
            $decoded = @mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8');
            if ($decoded !== false) {
                $value = $decoded;
            }
        }
        return trim($value);
    }
}
