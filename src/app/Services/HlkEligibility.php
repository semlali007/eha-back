<?php

namespace App\Services;

/**
 * Règles d'éligibilité métier appliquées aux données HLK.
 *
 * Extrait / partagé entre HlkBeneficiarySyncManager et HlkSheetImportManager
 * pour garantir que la même règle est appliquée à travers toutes les
 * fonctionnalités qui consomment HLK.
 */
class HlkEligibility
{
    /**
     * True si la valeur `contrat_ouvert_filiales` renvoyée par HLK indique OUI.
     *
     * Règle métier : HLK renvoie toujours le champ, mais peut être null ;
     * null est considéré comme false (contrat pas ouvert aux filiales → skip).
     * Idem pour toute autre valeur "négative" (false, 0, 'false', 'non', '').
     *
     * Tolère les représentations positives : booléen true, entier 1, chaînes
     * 'true'/'1'/'oui'/'yes' (insensible casse) — au cas où HLK sérialise
     * différemment selon la version de l'API.
     */
    public static function isContratOpenToFiliales(mixed $value): bool
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
}
