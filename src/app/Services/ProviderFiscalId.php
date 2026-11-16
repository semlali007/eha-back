<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Normalisation des identifiants fiscaux fournisseurs (TVA intracom, SIREN)
 * pour pouvoir les comparer entre HLK et la table provider.
 */
final class ProviderFiscalId
{
    /**
     * Normalise un n° de TVA intracommunautaire : majuscules, sans espaces ni
     * ponctuation. Retourne '' si la valeur n'a pas la forme d'une TVA
     * (2 lettres pays + 2 à 13 caractères alphanumériques), pour ignorer les
     * valeurs parasites ("N/A", "0", "-"...).
     */
    public static function normalizeVat(?string $vat): string
    {
        $v = strtoupper(preg_replace('/[^0-9A-Za-z]+/', '', (string) $vat) ?? '');
        return preg_match('/^[A-Z]{2}[0-9A-Z]{2,13}$/', $v) === 1 ? $v : '';
    }

    /**
     * Déduit le SIREN d'une TVA française : FR + clé (2 chiffres) + SIREN
     * (9 chiffres). Ex. FR73418827655 → 418827655. '' si TVA non française.
     */
    public static function sirenFromVat(?string $vat): string
    {
        $v = self::normalizeVat($vat);
        return preg_match('/^FR\d{2}(\d{9})$/', $v, $m) === 1 ? $m[1] : '';
    }

    /**
     * Normalise un SIREN : chiffres uniquement. '' si ce n'est pas 9 chiffres.
     */
    public static function normalizeSiren(?string $siren): string
    {
        $s = preg_replace('/\D+/', '', (string) $siren) ?? '';
        return strlen($s) === 9 ? $s : '';
    }
}
