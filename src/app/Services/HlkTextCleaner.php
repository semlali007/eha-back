<?php

namespace App\Services;

/**
 * Helpers texte partagés par les managers de sync HLK (bénéficiaires,
 * auto-import). Sans état, sans BDD — juste de la normalisation.
 *
 * Les méthodes ici sont extraites de HlkBeneficiarySyncManager pour être
 * réutilisées par les futurs managers HLK (auto-import de fiches p.ex.).
 * Semantic UNCHANGED : chaque méthode reproduit à l'identique le comportement
 * privé qu'avait le manager avant extraction.
 */
class HlkTextCleaner
{
    /**
     * Corrige le double-encodage UTF-8 fréquent dans le payload HLK
     * (MarchÃ© → Marché) et trim les blancs. Idempotent : sans souci sur
     * les chaînes déjà propres.
     */
    public static function clean(?string $value): string
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

    /**
     * Normalise un nom pour comparaison / clé de cache in-memory.
     * Doit refléter exactement ce que `Beneficiary::get()` compare côté SQL :
     * LOWER(unaccent(TRIM(...))).
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
    public static function normalizeKey(string $name): string
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
}
