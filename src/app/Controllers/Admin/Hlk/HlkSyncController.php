<?php

namespace App\Controllers\Admin\Hlk;

use App\Controllers\Controller;
use App\Manager\HlkBeneficiarySyncManager;
use Exception;

/**
 * Endpoints admin de synchronisation depuis HLK.
 *
 * Une seule action : `syncBeneficiaries` — déclenchée par bouton dans l'écran
 * admin, elle rejoue les bénéficiaires de toutes les fiches existantes en base
 * à partir des données HLK.
 */
class HlkSyncController extends Controller
{
    /**
     * POST /admin/hlk/sync-beneficiaries[?dry_run=1]
     *
     * Rôle requis : ADMIN (configuré côté routing.php).
     *
     * Query param facultatif :
     *   - dry_run=1  → simulation, aucune écriture BDD. La réponse contient
     *                  un "preview" détaillé pour vérifier l'impact avant
     *                  de valider.
     *   - absent ou 0 → exécution réelle, écriture en base.
     *
     * Réponse : rapport JSON structuré (cf. HlkBeneficiarySyncManager::run()).
     */
    public function syncBeneficiaries()
    {
        // Traitement long (fetch HLK pagine, plusieurs min sur volumétrie complète)
        // — désactive la limite PHP par défaut (30s), sinon un `Fatal error:
        // Maximum execution time exceeded` empêche le skip-on-fail de tourner.
        set_time_limit(0);
        @ini_set('output_buffering', '0');
        @ini_set('zlib.output_compression', '0');

        // --- Keepalive streaming ---
        //
        // Le traitement dure ~5-6 min. Sans octets émis, nginx renvoie 504
        // (fastcgi_read_timeout, souvent 60s), et le WAF EDF bloque tout code
        // 5xx (PSSI). On envoie donc du whitespace tous les X pages pour
        // maintenir la connexion active. Le JSON final est concaténé après.
        //
        // Le client JS parse via response.json() : les espaces de tête sont
        // ignorés par la RFC 8259, aucun changement front nécessaire.
        header('Content-Type: application/json; charset=utf-8');
        header('X-Accel-Buffering: no'); // demande à nginx de ne pas bufferiser
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Connection: keep-alive');

        // Vider TOUT buffer PHP existant pour que flush() atteigne vraiment nginx
        while (ob_get_level() > 0) {
            @ob_end_flush();
        }

        // Un premier byte immédiat pour "réserver" la connexion HTTP 200 côté
        // WAF et éviter qu'il ferme après quelques secondes d'inactivité.
        echo str_repeat(' ', 1024);
        @flush();

        $dryRun = filter_var($_GET['dry_run'] ?? false, FILTER_VALIDATE_BOOLEAN);

        try {
            $manager = new HlkBeneficiarySyncManager();

            // Keepalive : envoie 256 octets d'espace + newline après chaque page.
            // 691 pages × ~257 bytes ≈ 178 KB de "bruit" au max, ignoré par le
            // parser JSON du client.
            $onProgress = static function (int $pageNum): void {
                echo str_repeat(' ', 256) . "\n";
                @flush();
            };

            $report = $manager->run($dryRun, $onProgress);

            echo json_encode($report);
            @flush();
            return null; // on a déjà écrit la réponse à la main
        } catch (Exception $ex) {
            // Toujours retourner 200 pour ne pas déclencher le blocage WAF.
            // Le status métier est dans le corps.
            echo json_encode([
                'status' => 500,
                'error'  => $ex->getMessage(),
            ]);
            @flush();
            return null;
        }
    }
}
