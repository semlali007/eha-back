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

        $tStart  = microtime(true);
        $allRows = [];
        $page    = 1;
        $totalPages = null;
        $totalReported = null; // total annoncé par HLK au 1er appel

        error_log("[import-hlk:fetch] ===== Début fetch HLK =====");
        error_log("[import-hlk:fetch] url={$url} signed_date>={$signedSince} page_limit={$pageLimit}");

        do {
            $payload = [
                'action_code' => $actionCode,
                'password'    => $password,
                'page'        => $page,
                'signed_date' => $signedSince,
                'limit'       => $pageLimit,
            ];

            $tPage = microtime(true);
            $response = HttpClientService::exec_post($url, $payload);
            $pageMs = round((microtime(true) - $tPage) * 1000);

            // Format observé : [ { "page":N, "total":T, "total_pages":P, "fiches_marches":[...] } ]
            // On tolère aussi la variante où c'est directement l'objet, pas un tableau.
            $envelope = is_array($response) && isset($response[0]) ? $response[0] : $response;

            if (!is_array($envelope) || !isset($envelope['fiches_marches'])) {
                error_log("[import-hlk:fetch] ⚠ réponse invalide page={$page} (fiches_marches manquant)");
                throw new HttpException("Réponse HLK invalide (fiches_marches manquant) page={$page}");
            }

            $rows          = $envelope['fiches_marches'];
            $totalPages    = $envelope['total_pages'] ?? ($page);
            $totalReported = $totalReported ?? ($envelope['total'] ?? null);

            $allRows = array_merge($allRows, $rows);
            error_log(sprintf(
                "[import-hlk:fetch] page=%d/%d reçues=%d cumul=%d (%dms)",
                $page, $totalPages, count($rows), count($allRows), $pageMs
            ));

            $page++;
        } while ($page <= $totalPages);

        $totalMs = round((microtime(true) - $tStart) * 1000);
        error_log(sprintf(
            "[import-hlk:fetch] ===== Fin fetch : %d lignes récupérées en %dms (total HLK annoncé=%s) =====",
            count($allRows), $totalMs, $totalReported ?? 'n/a'
        ));

        return $allRows;
    }
}
