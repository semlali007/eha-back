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

        $dryRun = filter_var($_GET['dry_run'] ?? false, FILTER_VALIDATE_BOOLEAN);

        try {
            $manager = new HlkBeneficiarySyncManager();
            $report  = $manager->run($dryRun);
            return $this->response($report);
        } catch (Exception $ex) {
            return $this->error($ex);
        }
    }
}
