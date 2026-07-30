<?php

namespace App\Controllers\Admin\Hlk;

use App\Controllers\Controller;
use App\Manager\HlkSheetImportManager;
use App\Models\SyncRun;
use Exception;

/**
 * Endpoint admin pour déclencher un batch d'auto-import HLK en fire-and-forget.
 *
 * Le batch peut prendre plusieurs minutes ; on ne veut pas garder la requête
 * HTTP ouverte pendant tout ce temps. On lance donc un process CLI détaché
 * (`php bin/console app:import-sheets:hlk ...`) et on retourne immédiatement
 * un 202 Accepted avec l'info de suivi.
 *
 * L'admin voit la progression dans l'écran d'historique (endpoint list-runs)
 * en polling — chaque run apparaît d'abord en status='running' puis passe à
 * 'success'/'partial'/'error' quand le batch se termine.
 */
class HlkTriggerController extends Controller
{
    /**
     * POST /admin/hlk/import-sheets/trigger
     *
     * Body JSON (optionnel) :
     *   {
     *     "signed_since": "YYYY-MM-DD",   // override du curseur — bouton "depuis date custom"
     *     "dry_run":      true|false      // défaut false — bouton "simuler"
     *   }
     *
     * Sans body : lance un batch normal ("Relancer maintenant"), utilise le
     * curseur courant depuis sync_state.
     *
     * Réponse 202 Accepted :
     *   { "accepted": true, "job_name": "...", "triggered_by": "admin:<NNI>",
     *     "signed_since": "...", "dry_run": bool, "message": "..." }
     *
     * Suivi : GET /admin/hlk/sync-runs (l'admin poll pour voir le run apparaître).
     */
    public function triggerImportSheets()
    {
        try {
            $payload = $this->readJsonBody();

            $signedSince = $this->validateSignedSince($payload['signed_since'] ?? null);
            $dryRun      = filter_var($payload['dry_run'] ?? false, FILTER_VALIDATE_BOOLEAN);

            // Identité de l'admin qui a cliqué — traçabilité dans sync_runs.triggered_by
            $nni = 'unknown';
            if (isset($this->currentUser) && method_exists($this->currentUser, 'getNni')) {
                $nni = (string) $this->currentUser->getNni();
            }
            $triggeredBy = 'admin:' . $nni;

            $this->spawnBatch($signedSince, $dryRun, $triggeredBy);

            http_response_code(202);
            return $this->response([
                'accepted'     => true,
                'job_name'     => HlkSheetImportManager::JOB_NAME,
                'triggered_by' => $triggeredBy,
                'signed_since' => $signedSince,   // null → le curseur BDD sera utilisé
                'dry_run'      => $dryRun,
                'message'      => 'Batch lancé en arrière-plan. Suivre la progression via GET /admin/hlk/sync-runs.',
            ]);
        } catch (Exception $ex) {
            return $this->error($ex);
        }
    }

    // ==================== Helpers ====================

    /**
     * Lit le body JSON de la requête. Retourne un array vide si pas de body.
     */
    private function readJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if (!is_string($raw) || $raw === '') return [];
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Valide signed_since : doit être YYYY-MM-DD, sinon null (le batch utilisera
     * le curseur courant depuis sync_state).
     */
    private function validateSignedSince($value): ?string
    {
        if (!is_string($value) || $value === '') return null;
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? $value : null;
    }

    /**
     * Spawn un process CLI détaché pour le batch. Fire-and-forget :
     * la requête HTTP renvoie immédiatement, le batch continue en arrière-plan.
     *
     * Détails d'implémentation :
     *   - Sur Linux/Unix : `nohup ... > /dev/null 2>&1 &` — process détaché
     *     de la session PHP-FPM, survit à la fin de la requête HTTP.
     *   - Sur Windows : `start /B` — équivalent, background sans nouvelle fenêtre.
     *   - Args échappés via escapeshellarg pour éviter toute injection depuis
     *     le body JSON (signed_since est validé mais on ceinture-et-bretelles).
     *
     * ⚠ Prérequis serveur : PHP_BINARY doit être exécutable, et php-fpm doit
     * avoir le droit de fork (généralement OK, sauf si disable_functions
     * inclut exec/proc_open — à vérifier en preprod).
     */
    private function spawnBatch(?string $signedSince, bool $dryRun, string $triggeredBy): void
    {
        $php     = PHP_BINARY;
        $console = realpath(__DIR__ . '/../../../../bin/console');
        if ($console === false) {
            throw new Exception('bin/console introuvable — impossible de lancer le batch');
        }

        // Construction args
        $args = ['app:import-sheets:hlk'];
        if ($signedSince !== null) {
            $args[] = '--since=' . $signedSince;
        }
        if ($dryRun) {
            $args[] = '--dry-run';
        }
        $args[] = '--triggered-by=' . $triggeredBy;

        // Escape systématique
        $escapedArgs = array_map('escapeshellarg', $args);
        $cmd         = escapeshellarg($php) . ' ' . escapeshellarg($console) . ' ' . implode(' ', $escapedArgs);

        if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
            // Windows : start /B lance en background sans nouvelle fenêtre.
            // Le pclose(popen(..., 'r')) détache proprement du process PHP-FPM.
            pclose(popen('start /B ' . $cmd, 'r'));
        } else {
            // Linux/Unix : nohup + & = process détaché, survit à la fin de la
            // requête HTTP. /dev/null pour ne pas polluer nginx/php-fpm.
            exec('nohup ' . $cmd . ' > /dev/null 2>&1 &');
        }

        error_log("[hlk-trigger] batch lancé en background | cmd={$cmd}");
    }
}
