<?php

namespace App\Services;

use App\Models\Sheet;

/**
 * Cache in-memory du lookup `contract_code → fiche(s) EHA rattachée(s)`.
 *
 * Extrait de HlkBeneficiarySyncManager pour être partagé entre :
 *   - HlkBeneficiarySyncManager (rattachement des bénéficiaires)
 *   - HlkSheetImportManager     (auto-import : sait si une fiche existe déjà)
 *
 * Un contrat HLK peut apparaître dans des dizaines de rows (produit cartésien
 * contrat × option × bénéficiaire) — sans ce cache, chaque row déclencherait
 * un `sheet.getByContract()` = SQL JOIN, plusieurs milliers d'aller-retours DB
 * pour rien. Avec le cache : une seule requête SQL par contract_code unique.
 *
 * Usage :
 *   $cache = new ContractLookupCache(new Sheet());
 *   $sheets = $cache->getSheets('C4T2295770');  // [{id, label}, ...] ou []
 */
class ContractLookupCache
{
    /** @var array<string,array<int,array{id:int,label:string}>> Cache par contract_code */
    private array $cache = [];

    private Sheet $sheetModel;

    public function __construct(Sheet $sheetModel)
    {
        $this->sheetModel = $sheetModel;
    }

    /**
     * Rend la liste des fiches EHA rattachées à ce contract_code.
     * Cache miss → un `sheet.getByContract()` (SQL). Cache hit → retour direct.
     *
     * @return array<int,array{id:int,label:string}> Liste vide si aucune fiche.
     */
    public function getSheets(string $contractCode): array
    {
        if (isset($this->cache[$contractCode])) {
            return $this->cache[$contractCode];
        }
        $found = $this->sheetModel->getByContract($contractCode);
        $rows  = [];
        foreach ($found ?? [] as $r) {
            $rows[] = ['id' => (int) $r['id'], 'label' => $r['label'] ?? ''];
        }
        $this->cache[$contractCode] = $rows;
        return $rows;
    }

    /**
     * Indique si le contract_code a déjà été résolu (hit ou miss).
     * Utile pour distinguer les logs "premier appel" vs "retour cache".
     */
    public function isCached(string $contractCode): bool
    {
        return isset($this->cache[$contractCode]);
    }
}
