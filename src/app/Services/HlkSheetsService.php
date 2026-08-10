<?php

namespace App\Services;

use App\Exceptions\HttpException;
use Core\Util\Json;

/**
 * Client HLK pour récupérer les fiches marché.
 *
 * Utilisé par la sync bénéficiaires (endpoint admin déclenché par bouton) ET
 * par l'auto-import des fiches (HlkSheetImportManager) — même client partagé.
 * Récupère toutes les fiches en paginant, avec `is_contrat_groupe=true` pour
 * que HLK ne renvoie que les contrats groupe (filtre côté HLK plutôt que de
 * tout rapatrier pour jeter le superflu côté EHA).
 *
 * Certains records HLK ne sont pas sérialisables par leur backend et font
 * hanger la page entière (ticket ouvert côté équipe HLK). Le fetch est donc
 * en mode **skip-on-fail** : une page qui échoue est loguée et ignorée, la
 * boucle continue avec la suivante. Le consommateur récupère la liste des
 * pages skippées via getLastFetchInfo() pour la remonter au métier.
 *
 * L'API renvoie un produit cartésien contrat × option × bénéficiaire :
 *   - contrat_numero          : code du contrat (clé de rattachement côté EHA)
 *   - contrat_beneficiaire    : nom de la filiale bénéficiaire (nullable)
 *   - achat_id_cockpit        : identifiant Cockpit de l'achat
 *   - eb_libelle              : libellé du bénéficiaire (entité bénéficiaire)
 */
class HlkSheetsService
{
    public const DEFAULT_PAGE_LIMIT = 100;

    // Le timeout cURL est hardcodé à 30s dans HttpClientService::init().
    // Une page HLK saine répond en ~1s ; au-delà c'est un record en défaut
    // côté HLK — le catch d'HttpException dans streamAllPages skippe la page.

    /**
     * @var array{
     *   total_pages_hlk:int,
     *   pages_fetched:int,
     *   pages_skipped:int[],
     *   rows_fetched:int,
     *   coverage_ratio:float
     * }|null
     */
    private ?array $lastFetchInfo = null;

    /**
     * Itère sur les lignes HLK page par page, en yieldant chaque page dès
     * réception. Une page en échec est skippée immédiatement (sans retry)
     * et loguée, la boucle continue avec la suivante.
     *
     * @param string|null $signedSince Filtre `signed_date` HLK (YYYY-MM-DD).
     *   - null : pas de filtre envoyé (comportement sync bénéf)
     *   - date : envoie `signed_date` dans le payload (auto-import incrémental)
     *
     * @return \Generator<int, array<int,array>, mixed, void>  Yield une liste
     *   de lignes HLK par itération. L'index yield est le numéro de page.
     */
    public function streamAllPages(
        int $pageLimit = self::DEFAULT_PAGE_LIMIT,
        ?string $signedSince = null
    ): \Generator {
        $actionCode = HLK['actions']['market_sheets']['list'];
        $url        = HLK['url'];
        $password   = HLK['pwd'];

        $tStart        = microtime(true);
        $totalRows     = 0;
        $pagesFetched  = 0;
        $pagesSkipped  = [];
        $page          = 1;
        $totalPagesHlk = 1;

        error_log("[hlk-benef-sync:fetch] ===== Début stream HLK =====");
        error_log("[hlk-benef-sync:fetch] url={$url} page_limit={$pageLimit}" . ($signedSince !== null ? " signed_date={$signedSince}" : ' (sans filtre)'));

        do {
            $payload = [
                'action_code'        => $actionCode,
                'password'           => $password,
                'page'               => $page,
                'limit'              => $pageLimit,
                // Filtre côté HLK : ne renvoie que les contrats groupe, au
                // lieu de tout rapatrier puis filtrer côté EHA (cf.
                // HlkEligibility::isContratOpenToFiliales, toujours appliqué
                // en aval en filet de sécurité).
                'is_contrat_groupe'  => true,
            ];
            if ($signedSince !== null && $signedSince !== '') {
                $payload['signed_date'] = $signedSince;
            }

            try {
                $envelope = $this->fetchPage($url, $payload, $page);
            } catch (HttpException $e) {
                $pagesSkipped[] = $page;
                error_log("[hlk-benef-sync:fetch] ⚠ SKIP page={$page} : {$e->getMessage()}");
                $page++;
                continue;
            }

            if (!is_array($envelope) || !isset($envelope['fiches_marches'])) {
                $pagesSkipped[] = $page;
                error_log("[hlk-benef-sync:fetch] ⚠ SKIP page={$page} : réponse HLK invalide (fiches_marches manquant)");
                $page++;
                continue;
            }

            $rows          = $envelope['fiches_marches'];
            $totalPagesHlk = (int) ($envelope['total_pages'] ?? $page);
            $totalRows    += count($rows);
            $pagesFetched++;

            error_log(sprintf(
                "[hlk-benef-sync:fetch] page=%d/%d reçues=%d cumul=%d",
                $page, $totalPagesHlk, count($rows), $totalRows
            ));

            yield $page => $rows;

            $page++;
        } while ($page <= $totalPagesHlk);

        $this->lastFetchInfo = [
            'total_pages_hlk' => $totalPagesHlk,
            'pages_fetched'   => $pagesFetched,
            'pages_skipped'   => $pagesSkipped,
            'rows_fetched'    => $totalRows,
            'coverage_ratio'  => $totalPagesHlk > 0 ? round($pagesFetched / $totalPagesHlk, 3) : 1.0,
        ];

        $totalMs = round((microtime(true) - $tStart) * 1000);
        error_log(sprintf(
            "[hlk-benef-sync:fetch] ===== Fin stream : %d lignes / %d pages OK / %d pages skipées / %d pages HLK en %dms =====",
            $totalRows, $pagesFetched, count($pagesSkipped), $totalPagesHlk, $totalMs
        ));
        if (!empty($pagesSkipped)) {
            error_log("[hlk-benef-sync:fetch] Pages skipées : " . implode(', ', $pagesSkipped));
        }
    }

    /**
     * Rend le rapport d'exécution du dernier stream. Le consommateur l'inclut
     * dans son propre rapport pour signaler au métier les pages manquantes.
     */
    public function getLastFetchInfo(): ?array
    {
        return $this->lastFetchInfo;
    }

    /**
     * Appelle HLK pour une page donnée. Un seul essai — si ça échoue, le
     * caller skippe la page et passe à la suivante.
     *
     * @return array Enveloppe HLK (décodée en JSON)
     * @throws HttpException si l'appel échoue
     */
    private function fetchPage(string $url, array $payload, int $page): array
    {
        $tPage = microtime(true);
        $response = HttpClientService::exec_post(
            $url,
            $payload,
            Json::DEFAULT_HEADER
        );
        $pageMs = round((microtime(true) - $tPage) * 1000);
        error_log("[hlk-benef-sync:fetch] page={$page} OK ({$pageMs}ms)");

        // Format observé : [ { "page":N, "total_pages":P, "fiches_marches":[...] } ]
        // On tolère la variante où c'est directement l'objet.
        return is_array($response) && isset($response[0]) ? $response[0] : $response;
    }
}
