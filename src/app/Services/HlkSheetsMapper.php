<?php

namespace App\Services;

use App\Models\Beneficiary;
use App\Models\Category;

/**
 * Transforme le payload brut HLK (produit cartésien contrat × option × bénéficiaire)
 * en fiches EHA prêtes à être insérées via SheetManager::add().
 *
 * Règles clés :
 *  - Granularité : 1 fiche = 1 achat_id_cockpit
 *  - Agrégation : dédup des contrats, providers, filiales, options par groupe
 *  - Encoding : double-UTF8 depuis HLK → corrigé via mb_convert_encoding
 *  - Catégorie : lookup exact case-insensitive sur category.label, NULL sinon
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

    /** @var array<string,int|null> Cache: label → category_code */
    private array $categoryCache = [];

    /** @var array<string,int> Cache: label → beneficiary_id */
    private array $beneficiaryCache = [];

    /**
     * Point d'entrée : reçoit toutes les lignes brutes de HLK, retourne une liste
     * de fiches prêtes à être insérées.
     *
     * @param array $rawRows Lignes brutes HLK (produit cartésien)
     * @return array<int,array> Une entrée par fiche EHA (une par achat_id_cockpit)
     */
    public function transform(array $rawRows): array
    {
        // 1) Groupement par achat_id_cockpit
        $groups = [];
        foreach ($rawRows as $row) {
            $achatId = $row['achat_id_cockpit'] ?? null;
            if ($achatId === null || $achatId === '') {
                continue; // ligne inexploitable, on skippe silencieusement
            }
            $groups[$achatId][] = $row;
        }

        // 2) Agrégation de chaque groupe en une fiche EHA
        $sheets = [];
        foreach ($groups as $achatId => $rows) {
            $sheets[] = $this->buildSheetFromGroup((string) $achatId, $rows);
        }
        return $sheets;
    }

    /**
     * Construit une fiche EHA à partir des lignes HLK d'un même achat_id_cockpit.
     */
    private function buildSheetFromGroup(string $achatId, array $rows): array
    {
        $first = $rows[0];

        $label      = $this->cleanText($first['achat_libelle'] ?? '');
        $categoryLb = $this->cleanText($first['achat_categorie'] ?? '');
        $category   = $this->lookupCategoryCode($categoryLb);

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
            // Identifiants pour l'idempotence
            'netachat_id'   => (int) $achatId,
            'netachat_type' => self::NETACHAT_TYPE,

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
        // Format compatible avec SheetManager::add() : [ ['code' => label] ]
        // (le code est le nom faute d'ID côté HLK — le mapping HLK V2 arrivera plus tard)
        return array_map(fn($name) => ['code' => $name, 'name' => $name], array_keys($seen));
    }

    private function aggregateFiliales(array $rows): array
    {
        $seen = [];
        foreach ($rows as $r) {
            $name = $this->cleanText($r['contrat_beneficiaire'] ?? '');
            if ($name === '') continue;
            $seen[$name] = true;
        }
        $out = [];
        foreach (array_keys($seen) as $name) {
            $id = $this->resolveOrCreateBeneficiary($name);
            if ($id !== null) {
                $out[] = ['code' => $id, 'name' => $name];
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
        $out = [];
        foreach (array_keys($seen) as $name) {
            $id = $this->resolveOrCreateBeneficiary($name); // même table beneficiary
            if ($id !== null) {
                $out[] = ['code' => $id, 'name' => $name];
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
            $byKey[$key] = [
                'start_date' => $start,
                'end_date'   => $end,
                'label'      => $label,
            ];
        }
        return array_values($byKey);
    }

    // ==================== Lookups référentiels ====================

    /**
     * Cherche le code catégorie EHA correspondant au libellé HLK (case-insensitive).
     * Retourne null si aucune correspondance : le gestionnaire complètera.
     */
    private function lookupCategoryCode(string $label): ?int
    {
        if ($label === '') return null;
        $normalized = mb_strtolower(trim($label));
        if (array_key_exists($normalized, $this->categoryCache)) {
            return $this->categoryCache[$normalized];
        }
        try {
            $category = new Category();
            // On suppose que Category::getList() retourne tous les items ; on filtre en PHP.
            $all = $category->getList();
            foreach ($all as $c) {
                if (isset($c['label']) && mb_strtolower(trim($c['label'])) === $normalized) {
                    $this->categoryCache[$normalized] = (int) $c['code'];
                    return $this->categoryCache[$normalized];
                }
            }
        } catch (\Throwable $e) {
            error_log('[HlkSheetsMapper] lookupCategoryCode failed: ' . $e->getMessage());
        }
        $this->categoryCache[$normalized] = null;
        return null;
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
                return $newId;
            }
            $found = $model->get(['name' => $name]);
            if (is_array($found) && count($found) > 0 && isset($found[0]['id'])) {
                $this->beneficiaryCache[$normalized] = (int) $found[0]['id'];
                return $this->beneficiaryCache[$normalized];
            }
        } catch (\Throwable $e) {
            error_log('[HlkSheetsMapper] resolveOrCreateBeneficiary failed for "' . $name . '": ' . $e->getMessage());
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
