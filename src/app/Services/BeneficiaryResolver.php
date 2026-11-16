<?php

namespace App\Services;

use App\Models\Beneficiary;
use Throwable;

/**
 * Résout un nom de filiale (bénéficiaire ou autre) vers son ID en base :
 * lookup avec cache in-memory, puis création si absent (ou "création virtuelle"
 * en dry-run).
 *
 * Extrait de HlkBeneficiarySyncManager pour être partagé entre :
 *   - HlkBeneficiarySyncManager (sync bénéficiaires seuls)
 *   - HlkSheetImportManager     (auto-import de fiches, résout aussi filiales)
 *
 * Sémantique UNCHANGED par rapport à HlkBeneficiarySyncManager::
 * resolveOrCreateBeneficiary — mêmes contrats en dry-run et en réel, même
 * cache key normalisée (LOWER + unaccent + TRIM) pour matcher le SQL de
 * Beneficiary::get() côté PostgreSQL.
 *
 * Usage :
 *   $resolver = new BeneficiaryResolver(new Beneficiary());
 *   $id = $resolver->resolve('Enedis', $dryRun, $stats, $preview);
 */
class BeneficiaryResolver
{
    /** @var array<string,int> Cache : nom_normalisé → filiale.id */
    private array $cache = [];

    private Beneficiary $model;

    /** Préfixe utilisé dans les error_log — permet de distinguer les contextes
     *  d'usage (sync bénéf vs auto-import) dans les logs partagés. */
    private string $logPrefix;

    public function __construct(Beneficiary $model, string $logPrefix = '{$this->logPrefix}')
    {
        $this->model     = $model;
        $this->logPrefix = $logPrefix;
    }

    /**
     * Cherche un bénéficiaire par nom exact ; crée s'il n'existe pas.
     *
     * En dry_run : lookup normal ; si absent → aucune écriture, on ajoute au
     * preview `new_beneficiaries` et on retourne 0 (ID "virtuel") pour que le
     * flux appelant continue et compte les futurs links comme d'habitude.
     *
     * En réel : création via `Beneficiary::add(['name' => $name])` qui utilise
     * un ON CONFLICT DO NOTHING (retour 0 en cas de conflit — on refait un
     * lookup en fallback).
     *
     * Toutes les erreurs sont capturées dans `$stats['errors']` — le caller
     * reçoit `null` et décide quoi faire.
     *
     * @param string $name Nom brut du bénéficiaire (déjà cleanText'é si besoin).
     * @param bool $dryRun Mode simulation — pas d'écriture BDD.
     * @param array &$stats Compteurs + erreurs — mis à jour en place.
     * @param array &$preview Preview du rapport — mis à jour en place.
     * @return int|null ID filiale (0 en dry-run pour "à créer"), null si échec.
     */
    public function resolve(string $name, bool $dryRun, array &$stats, array &$preview): ?int
    {
        $key = HlkTextCleaner::normalizeKey($name);
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }
        try {
            $found = $this->model->get(['name' => $name]);
            if (is_array($found) && count($found) > 0 && isset($found[0]['id'])) {
                $this->cache[$key] = (int) $found[0]['id'];
                return $this->cache[$key];
            }
            // Le bénéficiaire n'existe pas.
            if ($dryRun) {
                // On note ce qui serait créé, sans écrire.
                $preview['new_beneficiaries'][$name] = true;
                $stats['beneficiaries_created']++;
                error_log("{$this->logPrefix} filiale '{$name}' À CRÉER (dry-run)");
                // ID virtuel 0 pour permettre au flux de continuer et compter les links
                $this->cache[$key] = 0;
                return 0;
            }
            // Création réelle (ON CONFLICT DO NOTHING — retour 0 si conflit)
            $newId = (int) $this->model->add(['name' => $name]);
            if ($newId > 0) {
                $this->cache[$key] = $newId;
                $stats['beneficiaries_created']++;
                // Audit : liste des filiales réellement créées (même clé que
                // le preview dry-run pour un affichage unifié côté front).
                $preview['new_beneficiaries'][$name] = true;
                error_log("{$this->logPrefix} filiale '{$name}' CRÉÉE (id={$newId})");
                return $newId;
            }
            // Fallback : re-lookup si ON CONFLICT n'a rien retourné
            $found = $this->model->get(['name' => $name]);
            if (is_array($found) && count($found) > 0 && isset($found[0]['id'])) {
                $this->cache[$key] = (int) $found[0]['id'];
                return $this->cache[$key];
            }
        } catch (Throwable $e) {
            $stats['errors'][] = [
                'beneficiary' => $name,
                'message'     => 'resolveOrCreateBeneficiary: ' . $e->getMessage(),
            ];
            error_log("{$this->logPrefix} ⚠ resolve filiale '{$name}' échec : " . $e->getMessage());
        }
        return null;
    }
}
