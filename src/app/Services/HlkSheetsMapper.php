<?php

namespace App\Services;

use App\Models\Beneficiary;
use App\Models\Category;
use App\Models\Provider;

/**
 * Transforme le payload brut HLK (produit cartésien contrat × option × bénéficiaire)
 * en fiches EHA prêtes à être insérées via SheetManager::add().
 *
 * Règles clés :
 *  - Granularité : 1 fiche = 1 achat_id_cockpit
 *  - Agrégation : dédup des contrats, providers, filiales, options par groupe
 *  - Encoding : double-UTF8 depuis HLK → corrigé via mb_convert_encoding
 *  - Catégorie : lookup accent-insensible sur category.label ; création auto si absente
 *  - Bénéficiaires : lookup exact, création automatique si absent
 *  - Dauphin : achat_dauphin (niveau achat, faisant foi)
 *  - Idempotence : netachat_id = achat_id_cockpit, netachat_type = 'hlk'
 */
class HlkSheetsMapper
{
    /**
     * Valeur posée dans sheet_headers.netachat_type pour toute fiche créée par
     * ce batch. PURELY INFORMATIVE : sert de marqueur d'origine ("cette fiche
     * a été importée depuis HLK") pour de futures requêtes / analyses, mais
     * n'entre PAS dans la logique d'idempotence (le lookup se fait par
     * netachat_id seul, cf. Sheet::findByNetachatId).
     */
    public const NETACHAT_TYPE = 'hlk';

    /** @var array<string,int|null> Cache: label normalisé → category_code */
    private array $categoryCache = [];

    /** @var array<string,int> Cache: label → beneficiary_id */
    private array $beneficiaryCache = [];

    /** @var array<string,int> Cache: label → provider_code */
    private array $providerCache = [];

    /** @var int Compteur de fournisseurs créés pendant transform() */
    private int $newProvidersCount = 0;

    /** @var int Compteur de fournisseurs HLK non résolus (nom vide ou erreur SQL) */
    private int $unknownProvidersCount = 0;

    /**
     * Statistiques d'usage par label HLK de catégorie.
     * Structure : [ label_HLK_original => [
     *     'count'   => nb de fiches ayant cette catégorie,
     *     'code'    => code EHA final (null si résolution impossible),
     *     'created' => true si nouvelle catégorie ajoutée à EHA pendant ce run
     *   ] ]
     */
    private array $categoryUsage = [];

    /**
     * Point d'entrée : reçoit toutes les lignes brutes de HLK, retourne une liste
     * de fiches prêtes à être insérées.
     *
     * @param array $rawRows Lignes brutes HLK (produit cartésien)
     * @return array<int,array> Une entrée par fiche EHA (une par achat_id_cockpit)
     */
    public function transform(array $rawRows): array
    {
        error_log("[import-hlk:map] ===== Début agrégation =====");
        error_log("[import-hlk:map] input=" . count($rawRows) . " lignes brutes (produit cartésien)");

        // 1) Groupement par achat_id_cockpit
        $groups = [];
        $skippedNoAchatId = 0;
        foreach ($rawRows as $row) {
            $achatId = $row['achat_id_cockpit'] ?? null;
            if ($achatId === null || $achatId === '') {
                $skippedNoAchatId++;
                continue; // ligne inexploitable
            }
            $groups[$achatId][] = $row;
        }
        if ($skippedNoAchatId > 0) {
            error_log("[import-hlk:map] ⚠ {$skippedNoAchatId} ligne(s) sans achat_id_cockpit ignorée(s)");
        }
        error_log("[import-hlk:map] " . count($groups) . " achats uniques après groupement");

        // 2) Agrégation de chaque groupe en une fiche EHA
        $sheets = [];
        foreach ($groups as $achatId => $rows) {
            $sheets[] = $this->buildSheetFromGroup((string) $achatId, $rows);
        }

        error_log(sprintf(
            "[import-hlk:map] ===== Fin agrégation : %d fiches | fournisseurs créés: %d (non résolus: %d) | bénéficiaires créés: %d | encoding fixé: %d =====",
            count($sheets),
            $this->newProvidersCount,
            $this->unknownProvidersCount,
            $this->newBeneficiariesCount,
            $this->encodingFixCount
        ));

        $this->logCategoryUsageReport();
        return $sheets;
    }

    /**
     * Produit un log détaillé de l'usage des catégories rencontrées :
     * pour chaque libellé HLK, combien de fiches l'ont utilisé, si elle
     * existait déjà en base ou vient d'être créée, et son code final.
     *
     * Utile pour le métier : identifier les catégories "poids lourd" à
     * bien rattacher au bon référentiel EHA, ou repérer d'éventuelles
     * catégories créées à tort (typo côté HLK, doublon proche…).
     */
    private function logCategoryUsageReport(): void
    {
        if (empty($this->categoryUsage)) {
            return;
        }

        // Tri par usage décroissant (les plus utilisées en tête)
        uasort($this->categoryUsage, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        error_log("[import-hlk:map] ===== Rapport d'usage des catégories =====");
        error_log("[import-hlk:map]     count | statut     | code | libellé HLK");
        error_log("[import-hlk:map]     ------+------------+------+---------------------------");
        foreach ($this->categoryUsage as $label => $info) {
            $status = $info['code'] === null
                ? 'NULL'
                : ($info['created'] ? 'CRÉÉE'    : 'existante');
            error_log(sprintf(
                "[import-hlk:map]     %5d | %-10s | %4s | %s",
                $info['count'],
                $status,
                $info['code'] !== null ? (string) $info['code'] : '-',
                $label
            ));
        }
        error_log("[import-hlk:map] ==========================================");
    }

    /**
     * Getter pour que la commande puisse remonter le rapport dans sync_state.stats.
     *
     * @return array<string,array{count:int,code:int|null,created:bool}>
     */
    public function getCategoryUsage(): array
    {
        return $this->categoryUsage;
    }

    /** @var int Compteur de bénéficiaires créés pendant transform() (pour le log de fin) */
    private int $newBeneficiariesCount = 0;
    /** @var int Compteur de labels catégorie non résolus (posés à null côté fiche) */
    private int $unknownCategoriesCount = 0;
    /** @var int Compteur de chaînes qui ont eu besoin d'un fix d'encoding double-UTF8 */
    private int $encodingFixCount = 0;

    /**
     * Construit une fiche EHA à partir des lignes HLK d'un même achat_id_cockpit.
     */
    private function buildSheetFromGroup(string $achatId, array $rows): array
    {
        $first = $rows[0];
        error_log("[import-hlk:map]   → achat_id={$achatId} (" . count($rows) . " ligne(s) HLK à agréger)");

        $label      = $this->cleanText($first['achat_libelle'] ?? '');
        $categoryLb = $this->cleanText($first['achat_categorie'] ?? '');
        $category   = $this->resolveOrCreateCategory($categoryLb);

        // Contracts, providers, filiales, filiales0, entités bénéficiaires : agrégés
        $contracts       = $this->aggregateContracts($rows);
        $providers       = $this->aggregateProviders($rows);
        $filiales        = $this->aggregateFiliales($rows);
        $filiales0       = $this->aggregateFiliales0($rows);
        $ebLibelles      = $this->aggregateEbLibelles($rows);

        // Dates : converties depuis epoch millis (string) → 'YYYY-MM-DD'
        $startDate = $this->msToDate($first['contrat_debut']    ?? null);
        $endDate   = $this->msToDate($first['contrat_fin_max']  ?? null);

        // Booléens
        $dauphin       = (bool) ($first['achat_dauphin']          ?? false);
        $contratGroupe = (bool) ($first['contrat_ouvert_filiales'] ?? false);

        return [
            // Clé d'idempotence canonique : l'identifiant Cockpit de l'achat
            'achat_id_cockpit' => (int) $achatId,
            // Marqueur d'origine (informatif, non utilisé pour le lookup)
            'netachat_type'    => self::NETACHAT_TYPE,

            // Informations générales
            'label'         => $label,
            'category'      => $category,          // int|null (code)
            'contrat_groupe'=> $contratGroupe ? 'true' : 'false',

            // Calendrier
            'start_date'    => $startDate,
            'end_date'      => $endDate,
            'options'       => $this->aggregateOptions($rows),

            // Fournisseurs & bénéficiaires
            'providers'                        => $providers,
            'supplier_vat_number'              => $this->cleanText($first['contrat_fournisseur_tva'] ?? ''),
            'filiales'                         => $filiales,
            'filiales0'                        => $filiales0,
            'entites_beneficiaires_edf_enedis' => $ebLibelles,

            // Contrats
            'contracts' => $contracts,

            // Conditions d'achat
            'dauphin'  => $dauphin,
            'edf_open' => false, // non fourni par HLK

            // Gestion
            'acheteur'         => $this->cleanText($first['achat_acheteur']         ?? ''),
            'contract_manager' => $this->cleanText($first['achat_contract_manager'] ?? ''),

            // Non fournis par HLK (valeurs par défaut)
            'description'           => '',
            'words'                 => '',
            'committee'             => null,
            'stipulation_for_others'=> false,
            'partnership'           => false,
            'protected_sector'      => false,
            'showcase'              => false,
            'mise_a_jour_donnee'    => 'non',
            'geographic_detail'     => null,
            'zones'                 => [],
            'lots'                  => [],
            'kits'                  => [],

            // Publication : brouillon par défaut
            'status' => 0,
            'draft'  => true,
        ];
    }

    // ==================== Agrégations ====================

    private function aggregateContracts(array $rows): array
    {
        $byKey = [];
        foreach ($rows as $r) {
            $code = $this->cleanText($r['contrat_numero'] ?? '');
            if ($code === '') continue;
            // Clé : contrat_id_cockpit si dispo, sinon (numero + fournisseur)
            $key = ($r['contrat_id_cockpit'] ?? '') . '|' . $code . '|' . ($r['contrat_fournisseur'] ?? '');
            if (isset($byKey[$key])) continue;
            $byKey[$key] = [
                'contract_code' => $code,
                'provider'      => $this->cleanText($r['contrat_fournisseur'] ?? ''),
                'contacts'      => '',
                'lots'          => '',
            ];
        }
        return array_values($byKey);
    }

    private function aggregateProviders(array $rows): array
    {
        $seen = [];
        foreach ($rows as $r) {
            $name = $this->cleanText($r['contrat_fournisseur'] ?? '');
            if ($name === '') continue;
            $seen[$name] = true;
        }
        // Format attendu par SheetManager::add() : [ ['id' => provider_code_int, ...] ]
        // Il faut donc convertir chaque nom HLK en provider_code réel (existant en base
        // ou créé à la volée). Un nom non résolu → on ne le remonte pas (pas de lien).
        $out = [];
        foreach (array_keys($seen) as $name) {
            $code = $this->resolveOrCreateProvider($name);
            if ($code !== null) {
                $out[] = ['id' => $code, 'name' => $name];
            }
        }
        return $out;
    }

    /**
     * Cherche le provider_code EHA correspondant au nom HLK (lookup exact via
     * Provider::get). Crée l'entrée si absente via Provider::add.
     *
     * L'enrichissement (SIRET, NAF, TVA…) viendra plus tard via HLK V2. Pour
     * l'instant on se contente du provider_label pour ne pas bloquer la liaison
     * fiche ↔ fournisseur.
     */
    private function resolveOrCreateProvider(string $name): ?int
    {
        if ($name === '') return null;
        $normalized = mb_strtolower(trim($name));
        if (isset($this->providerCache[$normalized])) {
            return $this->providerCache[$normalized];
        }
        try {
            $model = new Provider();
            // Lookup exact
            $found = $model->get(['provider_label' => $name]);
            if (is_array($found) && count($found) > 0 && isset($found[0]['provider_code'])) {
                $this->providerCache[$normalized] = (int) $found[0]['provider_code'];
                return $this->providerCache[$normalized];
            }
            // Création via INSERT ... ON CONFLICT DO NOTHING (retourne lastInsertId
            // ou "0" si conflit). Si 0 → on re-lookup.
            $newCode = (int) $model->add(['provider_label' => $name]);
            if ($newCode > 0) {
                $this->providerCache[$normalized] = $newCode;
                $this->newProvidersCount++;
                error_log("[import-hlk:map]     · fournisseur '{$name}' CRÉÉ (code={$newCode})");
                return $newCode;
            }
            // Fallback : re-lookup au cas où ON CONFLICT DO NOTHING ait retourné 0
            $found = $model->get(['provider_label' => $name]);
            if (is_array($found) && count($found) > 0 && isset($found[0]['provider_code'])) {
                $this->providerCache[$normalized] = (int) $found[0]['provider_code'];
                return $this->providerCache[$normalized];
            }
        } catch (\Throwable $e) {
            error_log('[import-hlk:map] ⚠ resolveOrCreateProvider failed for "' . $name . '": ' . $e->getMessage());
        }
        $this->unknownProvidersCount++;
        error_log("[import-hlk:map]     · fournisseur '{$name}' NON RÉSOLU → pas de lien créé");
        return null;
    }

    private function aggregateFiliales(array $rows): array
    {
        $seen = [];
        foreach ($rows as $r) {
            $name = $this->cleanText($r['contrat_beneficiaire'] ?? '');
            if ($name === '') continue;
            $seen[$name] = true;
        }
        // Format attendu par SheetManager::addSheet() : ['id' => filiale_id, ...]
        // (pas 'code' — clé qui serait ignorée par le loop)
        $out = [];
        foreach (array_keys($seen) as $name) {
            $id = $this->resolveOrCreateBeneficiary($name);
            if ($id !== null) {
                $out[] = ['id' => $id, 'name' => $name];
            }
        }
        return $out;
    }

    private function aggregateFiliales0(array $rows): array
    {
        $seen = [];
        foreach ($rows as $r) {
            $name = $this->cleanText($r['contrat_stipulation'] ?? '');
            if ($name === '') continue;
            $seen[$name] = true;
        }
        // Idem : SheetManager::addSheet() attend 'id' pour les filiales0
        $out = [];
        foreach (array_keys($seen) as $name) {
            $id = $this->resolveOrCreateBeneficiary($name); // même table beneficiary
            if ($id !== null) {
                $out[] = ['id' => $id, 'name' => $name];
            }
        }
        return $out;
    }

    private function aggregateEbLibelles(array $rows): string
    {
        $seen = [];
        foreach ($rows as $r) {
            $lb = $this->cleanText($r['eb_libelle'] ?? '');
            if ($lb === '') continue;
            $seen[$lb] = true;
        }
        return implode(' | ', array_keys($seen));
    }

    private function aggregateOptions(array $rows): array
    {
        $byKey = [];
        foreach ($rows as $r) {
            $start = $this->msToDate($r['contrat_option_debut'] ?? null);
            $end   = $this->msToDate($r['contrat_option_fin']   ?? null);
            $label = $this->cleanText($r['contrat_option_libelle'] ?? '');
            if ($start === null && $end === null && $label === '') continue;
            $key = $start . '|' . $end . '|' . $label;
            if (isset($byKey[$key])) continue;
            // Attention : SheetOption::add() attend 'startdate' et 'enddate' (sans underscore),
            // à ne PAS confondre avec sheet_headers.start_date/end_date. Le champ 'label'
            // n'est pas persisté par SheetOption::add() aujourd'hui — limitation du modèle.
            $byKey[$key] = [
                'startdate' => $start,
                'enddate'   => $end,
                'label'     => $label,
            ];
        }
        return array_values($byKey);
    }

    // ==================== Lookups référentiels ====================

    /**
     * Cherche le code catégorie EHA correspondant au libellé HLK (case-insensitive).
     * Retourne null si aucune correspondance : le gestionnaire complètera.
     */
    /**
     * Cherche le code catégorie EHA correspondant au libellé HLK (matching
     * accent-insensible + lowercase + trim). Si aucune correspondance,
     * insère une nouvelle catégorie via Category::add() et retourne son code.
     *
     * Compte au passage l'usage par label HLK original pour le rapport final.
     */
    private function resolveOrCreateCategory(string $label): ?int
    {
        if ($label === '') return null;

        // Track usage (par label HLK original, pas normalisé, pour le reporting)
        if (!isset($this->categoryUsage[$label])) {
            $this->categoryUsage[$label] = ['count' => 0, 'code' => null, 'created' => false];
        }
        $this->categoryUsage[$label]['count']++;

        $normalized = $this->normalizeForMatch($label);
        if (array_key_exists($normalized, $this->categoryCache)) {
            $this->categoryUsage[$label]['code'] = $this->categoryCache[$normalized];
            return $this->categoryCache[$normalized];
        }

        try {
            $category = new Category();
            $all = $category->getList();
            foreach ($all as $c) {
                if (isset($c['label']) && $this->normalizeForMatch($c['label']) === $normalized) {
                    $code = (int) $c['code'];
                    $this->categoryCache[$normalized] = $code;
                    $this->categoryUsage[$label]['code'] = $code;
                    error_log("[import-hlk:map]     · catégorie '{$label}' → code {$code} (existante)");
                    return $code;
                }
            }
            // Absente → création
            $newCode = $category->add(['label' => $label]);
            if ($newCode !== null && $newCode > 0) {
                $this->categoryCache[$normalized] = $newCode;
                $this->categoryUsage[$label]['code']    = $newCode;
                $this->categoryUsage[$label]['created'] = true;
                error_log("[import-hlk:map]     · catégorie '{$label}' CRÉÉE (code={$newCode})");
                return $newCode;
            }
            // Fallback : ON CONFLICT DO NOTHING peut renvoyer null → re-lookup
            $all = $category->getList();
            foreach ($all as $c) {
                if (isset($c['label']) && $this->normalizeForMatch($c['label']) === $normalized) {
                    $code = (int) $c['code'];
                    $this->categoryCache[$normalized]     = $code;
                    $this->categoryUsage[$label]['code']  = $code;
                    return $code;
                }
            }
        } catch (\Throwable $e) {
            error_log('[import-hlk:map] ⚠ resolveOrCreateCategory failed for "' . $label . '": ' . $e->getMessage());
        }

        $this->categoryCache[$normalized] = null;
        $this->unknownCategoriesCount++;
        error_log("[import-hlk:map]     · catégorie '{$label}' NON RÉSOLUE → NULL (à revoir)");
        return null;
    }

    /**
     * Normalise un libellé pour un matching robuste : lowercase, trim et
     * suppression des accents. Aligne HLK et EHA malgré les variantes de saisie.
     */
    private function normalizeForMatch(string $s): string
    {
        $s = trim($s);
        // iconv translit + IGNORE = stripping accents robuste
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        if ($ascii === false) {
            $ascii = $s; // fallback : on garde tel quel si iconv échoue
        }
        return mb_strtolower($ascii);
    }

    /**
     * Cherche l'ID d'un bénéficiaire par nom (exact) ; crée l'entrée si absente.
     * Retourne l'ID ou null en cas d'échec.
     */
    private function resolveOrCreateBeneficiary(string $name): ?int
    {
        if ($name === '') return null;
        $normalized = mb_strtolower(trim($name));
        if (isset($this->beneficiaryCache[$normalized])) {
            return $this->beneficiaryCache[$normalized];
        }
        try {
            $model = new Beneficiary();
            // Lookup exact (case-sensitive côté SQL, mais HLK et EHA stockent en majuscules).
            $found = $model->get(['name' => $name]);
            if (is_array($found) && count($found) > 0 && isset($found[0]['id'])) {
                $this->beneficiaryCache[$normalized] = (int) $found[0]['id'];
                return $this->beneficiaryCache[$normalized];
            }
            // Création via INSERT ... ON CONFLICT DO NOTHING. Retourne lastInsertId
            // ou "0" si conflit — dans ce cas on re-lookup pour récupérer l'ID.
            $newId = (int) $model->add(['name' => $name]);
            if ($newId > 0) {
                $this->beneficiaryCache[$normalized] = $newId;
                $this->newBeneficiariesCount++;
                error_log("[import-hlk:map]     · bénéficiaire '{$name}' CRÉÉ (id={$newId})");
                return $newId;
            }
            $found = $model->get(['name' => $name]);
            if (is_array($found) && count($found) > 0 && isset($found[0]['id'])) {
                $this->beneficiaryCache[$normalized] = (int) $found[0]['id'];
                return $this->beneficiaryCache[$normalized];
            }
        } catch (\Throwable $e) {
            error_log('[import-hlk:map] ⚠ resolveOrCreateBeneficiary failed for "' . $name . '": ' . $e->getMessage());
        }
        return null;
    }

    // ==================== Helpers ====================

    /**
     * Corrige le double-encodage UTF-8 (MarchÃ© → Marché) et trim.
     * Compatible aussi avec des chaînes déjà propres (idempotent grâce au check).
     */
    private function cleanText(?string $value): string
    {
        if ($value === null || $value === '') return '';
        // Détection du double-encodage : présence de séquences Ã suivies d'un signe typique
        if (preg_match('/Ã[©¨ª«®¯»¸¡Â]/u', $value)) {
            $decoded = @mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8');
            if ($decoded !== false) {
                $this->encodingFixCount++;
                $value = $decoded;
            }
        }
        return trim($value);
    }

    /**
     * Convertit un timestamp epoch millis (string ou int, éventuellement null)
     * en date 'YYYY-MM-DD' pour PostgreSQL.
     */
    private function msToDate($value): ?string
    {
        if ($value === null || $value === '') return null;
        $ms = is_string($value) ? (int) $value : $value;
        if (!is_int($ms) || $ms <= 0) return null;
        return date('Y-m-d', (int) ($ms / 1000));
    }
}
