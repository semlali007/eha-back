<?php

namespace App\Manager;

use App\Models\Beneficiary;
use App\Models\Sheet;
use App\Models\SheetBeneficiary;
use App\Services\BeneficiaryResolver;
use App\Services\ContractLookupCache;
use App\Services\HlkEligibility;
use App\Services\HlkSheetsService;
use App\Services\HlkSyncReport;
use App\Services\HlkTextCleaner;
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
    /** Résolveur de filiales : cache + lookup + création (dry-run compatible). */
    private ?BeneficiaryResolver $beneficiaryResolver = null;

    /** Cache lookup contract_code → fiches EHA rattachées. */
    private ?ContractLookupCache $contractCache = null;

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
        $this->beneficiaryResolver = new BeneficiaryResolver($benefModel);
        $this->contractCache       = new ContractLookupCache($sheet);
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
            $skipWarning = HlkSyncReport::buildPagesSkippedWarning($fetchInfo);
            if ($skipWarning !== null) {
                $stats['warnings'][] = $skipWarning;
            }
        }

        // Rapport de couverture côté EHA : combien de fiches on a en base vs
        // combien ont été touchées par la sync. Permet au métier de juger le
        // résultat sans dépendre uniquement des stats HLK.
        $stats['coverage'] = HlkSyncReport::computeEhaCoverage(count($updatedSheets));

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
            $wasCached = $this->contractCache->isCached($contractCode);
            $tLookup = microtime(true);
            $matchedSheets = $this->contractCache->getSheets($contractCode);
            if (!$wasCached) {
                $lookupMs = round((microtime(true) - $tLookup) * 1000);
                error_log(sprintf(
                    '[hlk-benef-sync] row #%d (p%d) LOOKUP contract=%s → %d fiche(s) (%dms) [cache miss]',
                    $rowIndex, $pageNum, $contractCode, count($matchedSheets), $lookupMs
                ));
            }

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

    /** @see BeneficiaryResolver::resolve — délégation kept for backward compat. */
    private function resolveOrCreateBeneficiary(
        string $name,
        Beneficiary $model,       // conservé pour compat de signature — le resolver a déjà le modèle
        array &$stats,
        bool $dryRun,
        array &$preview
    ): ?int {
        return $this->beneficiaryResolver->resolve($name, $dryRun, $stats, $preview);
    }

    /** @see HlkTextCleaner::normalizeKey — délégation kept for backward compat. */
    private function normalizeKey(string $name): string
    {
        return HlkTextCleaner::normalizeKey($name);
    }

    /** @see HlkEligibility::isContratOpenToFiliales — délégation vers kernel partagé. */
    private function isOpenToFiliales(mixed $value): bool
    {
        return HlkEligibility::isContratOpenToFiliales($value);
    }

    /** @see HlkTextCleaner::clean — délégation kept for backward compat. */
    private function cleanText(?string $value): string
    {
        return HlkTextCleaner::clean($value);
    }
}
