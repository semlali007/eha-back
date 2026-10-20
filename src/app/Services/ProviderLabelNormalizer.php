<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Normalisation des libellés fournisseurs pour détecter les doublons.
 *
 * Deux libellés qui ont la même forme normalisée désignent le même
 * fournisseur : « CONSORT FRANCE », « Consort France SAS » et
 * « consort  france » → "consort france".
 *
 * Règles :
 *   1. strip du contenu entre parenthèses (acronymes, commentaires)
 *   2. esperluettes (&, ＆ fullwidth) → espace
 *   3. lower + suppression des accents
 *   4. ponctuation → espace + collapse whitespace
 *   5. strip itératif suffixes légaux français en fin (SA, SAS, SARL...)
 *   6. tokenize + strip stop-words FR + sort
 */
final class ProviderLabelNormalizer
{
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

    /** Champs pris en compte pour départager plusieurs fournisseurs similaires. */
    private const FILLED_FIELDS = ['provider_label', 'provider_siren', 'provider_numero_tva', 'provider_code_naf'];

    public static function normalize(?string $label): string
    {
        $s = trim((string) $label);
        if ($s === '') {
            return '';
        }
        $s = preg_replace('/\([^)]*\)/u', ' ', $s) ?? $s;
        $s = preg_replace('/[&\x{FF06}]/u', ' ', $s) ?? $s;
        $s = self::stripAccents(mb_strtolower($s, 'UTF-8'));
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
        $stopSet = array_flip(self::STOP_WORDS);
        $tokens = array_values(array_filter(
            explode(' ', $s),
            static fn(string $t): bool => $t !== '' && !isset($stopSet[$t])
        ));
        sort($tokens, SORT_STRING);
        return implode(' ', $tokens);
    }

    /**
     * Parmi deux fournisseurs similaires, indique si $candidate doit être
     * préféré à $current : 1) plus de champs renseignés, 2) provider_code le
     * plus ancien.
     *
     * @param array<string,mixed> $candidate
     * @param array<string,mixed> $current
     */
    public static function isBetterCandidate(array $candidate, array $current): bool
    {
        $ca = self::countFilledFields($candidate);
        $cb = self::countFilledFields($current);
        if ($ca !== $cb) {
            return $ca > $cb;
        }
        return (int) $candidate['provider_code'] < (int) $current['provider_code'];
    }

    /**
     * @param array<string,mixed> $row
     */
    private static function countFilledFields(array $row): int
    {
        $c = 0;
        foreach (self::FILLED_FIELDS as $f) {
            $v = $row[$f] ?? null;
            if ($v !== null && trim((string) $v) !== '') {
                $c++;
            }
        }
        return $c;
    }

    /**
     * Supprime les accents d'une chaîne déjà en minuscules.
     *
     * Pas d'iconv('ASCII//TRANSLIT') : son résultat dépend de la locale du
     * serveur (en locale "C", « é » devient « 'e » ou « ? »), ce qui découpe
     * les mots (« société » → « soci e e »).
     */
    private static function stripAccents(string $s): string
    {
        if (class_exists(\Normalizer::class)) {
            $decomposed = \Normalizer::normalize($s, \Normalizer::FORM_D);
            if ($decomposed !== false) {
                $s = preg_replace('/\p{Mn}+/u', '', $decomposed) ?? $s;
            }
        }
        // Ligatures (non décomposables) + repli si l'extension intl est absente
        return strtr($s, [
            'œ' => 'oe', 'æ' => 'ae', 'ß' => 'ss', 'ø' => 'o',
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a', 'å' => 'a',
            'ç' => 'c',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i', 'í' => 'i', 'ì' => 'i',
            'ô' => 'o', 'ö' => 'o', 'ó' => 'o', 'ò' => 'o', 'õ' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u',
            'ÿ' => 'y', 'ý' => 'y', 'ñ' => 'n',
        ]);
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
}
