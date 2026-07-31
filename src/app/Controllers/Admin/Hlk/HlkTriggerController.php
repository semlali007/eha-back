<?php

namespace App\Controllers\Admin\Hlk;

use App\Controllers\Controller;
use App\Manager\HlkSheetImportManager;
use Exception;

/**
 * Endpoint admin pour déclencher un batch d'auto-import HLK depuis l'UI.
 *
 * Modèle **synchrone avec keepalive streaming** (aligné sur HlkSyncController
 * pour la sync bénéf) :
 *   - L'endpoint appelle le manager DIRECTEMENT (pas de subprocess CLI)
 *   - Il émet des octets d'espace périodiquement pendant les 5-10 min du run
 *     pour maintenir la connexion HTTP ouverte (nginx / WAF EDF timeout ~60s
 *     sinon)
 *   - La réponse finale = le JSON complet du rapport (le front l'utilise
 *     directement, pas besoin de poll)
 *
 * L'historique complet est quand même écrit dans sync_runs par le manager
 * (le front peut afficher les runs cron nocturnes via GET /admin/hlk/sync-runs).
 */
class HlkTriggerController extends Controller
{
    /**
     * POST /admin/hlk/import-sheets/trigger
     *
     * Body JSON (optionnel) :
     *   {
     *     "signed_since": "YYYY-MM-DD",   // override du curseur — bouton "depuis date"
     *     "dry_run":      true|false      // défaut false — bouton "simuler"
     *   }
     *
     * Sans body : lance un batch normal ("Relancer maintenant"), utilise le
     * curseur courant depuis sync_state.
     *
     * Réponse : le rapport complet du run (cf. HlkSheetImportManager::run()).
     *   {
     *     "status": "success" | "partial" | "error",
     *     "dry_run": bool,
     *     "signed_since": "...",
     *     "next_cursor": "...",
     *     "created": N, "skipped_existing": N, ...
     *     "errors": [...], "warnings": [...],
     *     "sync_run_id": N   // pour retrouver le run dans l'historique
     *   }
     */
    public function triggerImportSheets()
    {
        // Traitement long (5-10 min) — désactive la limite PHP par défaut.
        set_time_limit(0);
        @ini_set('output_buffering', '0');
        @ini_set('zlib.output_compression', '0');

        // Le batch accumule TOUTES les rows HLK en mémoire avant l'agrégation
        // par le mapper (nécessaire car un achat peut couvrir plusieurs pages).
        // À gros volume (~40k rows × ~1KB), le défaut 128M sature — on relève
        // à 1G pour absorber toute la donnée HLK sans risque de fatal error.
        @ini_set('memory_limit', '1024M');

        // --- Keepalive streaming (même pattern que HlkSyncController) ---
        header('Content-Type: application/json; charset=utf-8');
        header('X-Accel-Buffering: no'); // demande à nginx de ne pas bufferiser
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Connection: keep-alive');

        // Vider TOUT buffer PHP existant pour que flush() atteigne vraiment nginx
        while (ob_get_level() > 0) {
            @ob_end_flush();
        }

        // Premier byte immédiat pour "réserver" la connexion HTTP 200 côté
        // WAF et éviter qu'il ferme après quelques secondes d'inactivité.
        // Les espaces de tête sont ignorés par le parser JSON (RFC 8259).
        echo str_repeat(' ', 1024);
        @flush();

        try {
            $payload = $this->readJsonBody();

            $signedSince = $this->validateSignedSince($payload['signed_since'] ?? null);
            $dryRun      = filter_var($payload['dry_run'] ?? false, FILTER_VALIDATE_BOOLEAN);

            // Identité admin — traçabilité dans sync_runs.triggered_by
            $nni = 'unknown';
            if (isset($this->currentUser) && method_exists($this->currentUser, 'getNni')) {
                $nni = (string) $this->currentUser->getNni();
            }
            $triggeredBy = 'admin:' . $nni;

            // Keepalive : après chaque étape du batch, émet 256 octets d'espace
            // + newline pour maintenir la connexion active côté nginx/WAF.
            // Le manager appelle ce callback aux étapes : page_fetched, map_done,
            // ingest_progress (tous les 20 sheets ingérés).
            $onProgress = static function (string $stage, array $partial): void {
                echo str_repeat(' ', 256) . "\n";
                @flush();
            };

            $manager = new HlkSheetImportManager();
            $report  = $manager->run($dryRun, $signedSince, $triggeredBy, $onProgress);

            echo json_encode($report);
            @flush();
            return null; // on a déjà écrit la réponse à la main
        } catch (Exception $ex) {
            // Toujours retourner 200 pour ne pas déclencher le blocage WAF.
            // Le status métier est dans le corps.
            echo json_encode([
                'status' => 'error',
                'error'  => $ex->getMessage(),
            ]);
            @flush();
            return null;
        }
    }

    // ==================== Helpers ====================

    private function readJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if (!is_string($raw) || $raw === '') return [];
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function validateSignedSince($value): ?string
    {
        if (!is_string($value) || $value === '') return null;
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? $value : null;
    }
}
