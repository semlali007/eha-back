<?php

namespace App\Services;

use App\Exceptions\HttpException;

/**
 * Client HLK v1 pour récupérer les fiches marché à importer.
 *
 * Appelle l'endpoint /eha avec action_code=get_market_sheet et gère la pagination
 * (l'API renvoie des pages de N résultats, on itère jusqu'à épuisement).
 *
 * Le payload de l'API est un produit cartésien contrat × option × bénéficiaire,
 * l'agrégation par achat_id_cockpit est faite en aval par HlkSheetsMapper.
 */
class HlkSheetsService
{
    private const DEFAULT_PAGE_LIMIT = 100;

    /**
     * Récupère toutes les fiches marché signées depuis $signedSince.
     *
     * @param string $signedSince Date au format 'YYYY-MM-DD' (curseur incrémental)
     * @param int    $pageLimit   Taille de page HLK (max recommandé : 100)
     * @return array Liste des lignes brutes (product cartésien), à agréger côté mapper
     */
    public function fetchAllSince(string $signedSince, int $pageLimit = self::DEFAULT_PAGE_LIMIT): array
    {
        $actionCode = HLK['actions']['market_sheets']['list'];
        $url        = HLK['url'];
        $password   = HLK['pwd'];

        $allRows = [];
        $page    = 1;
        $totalPages = null; // découvert au 1er appel

        do {
            $payload = [
                'action_code' => $actionCode,
                'password'    => $password,
                'page'        => $page,
                'signed_date' => $signedSince,
                'limit'       => $pageLimit,
            ];

            error_log("[HlkSheetsService] POST {$url} page={$page} signed_date={$signedSince} limit={$pageLimit}");
            $response = HttpClientService::exec_post($url, $payload);

            // Format observé : [ { "page":N, "total":T, "total_pages":P, "fiches_marches":[...] } ]
            // On tolère aussi la variante où c'est directement l'objet, pas un tableau.
            $envelope = is_array($response) && isset($response[0]) ? $response[0] : $response;

            if (!is_array($envelope) || !isset($envelope['fiches_marches'])) {
                throw new HttpException("Réponse HLK invalide (fiches_marches manquant) page={$page}");
            }

            $rows       = $envelope['fiches_marches'];
            $totalPages = $envelope['total_pages'] ?? ($page); // fallback : 1 seule page

            $allRows = array_merge($allRows, $rows);
            error_log("[HlkSheetsService] page={$page}/{$totalPages} rows_recues=" . count($rows) . " cumul=" . count($allRows));

            $page++;
        } while ($page <= $totalPages);

        return $allRows;
    }
}
