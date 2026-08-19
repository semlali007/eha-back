<?php

namespace App\Services;

use Core\Mailer\{Builder as Mailer, Exception};

/**
 * Notifie par email la BAL fonctionnelle Achatpédia des fiches marché créées
 * automatiquement depuis HLK (statut Brouillon, à vérifier avant publication).
 *
 * Un seul mail par lancement du batch, listant toutes les fiches créées lors
 * de ce run — pas un mail par fiche (trop de bruit quand un run en crée
 * plusieurs, cf. HlkSheetImportManager::run()).
 *
 * Il n'existe aucun moyen fiable de résoudre l'email personnel de l'acheteur
 * (le champ `acheteur` d'une fiche est un texte libre venant de HLK, sans lien
 * vers un référentiel utilisateur) — décision métier : la notification part
 * uniquement vers la BAL fonctionnelle configurée.
 */
class HlkSheetCreationNotifier
{
    /**
     * @param array<int, array{id:int, label:string, achat_id_cockpit:string}> $sheets
     *   Fiches créées lors du run. Ne fait rien si vide (rien à notifier).
     */
    public function notifyBatch(array $sheets): void
    {
        if (empty($sheets)) {
            return;
        }

        // BAL_ACHATPEDIA_FICHES_A_PUBLIER accepte un ou plusieurs destinataires
        // séparés par des virgules (ex: "a@edf.fr,b@edf.fr"). Builder::setRecepients()
        // ne découpe pas une string sur les virgules — elle la passerait telle
        // quelle à PHPMailer::addAddress(), qui n'accepte qu'UNE seule adresse.
        // On découpe donc nous-mêmes en tableau [email => ''].
        $recipients = array_filter(array_map(
            'trim',
            explode(',', (string) getenv('BAL_ACHATPEDIA_FICHES_A_PUBLIER'))
        ));
        if (empty($recipients)) {
            error_log('[hlk-sheet-import] notification création ignorée : BAL_ACHATPEDIA_FICHES_A_PUBLIER non configurée');
            return;
        }

        try {
            (new Mailer())
                ->setSubject('[Achatpédia] Nouvelles fiches marchés créées automatiquement')
                ->setRecepients(array_fill_keys($recipients, ''))
                ->html('mail/hlk_sheet_auto_created.html.twig', [
                    'sheets'        => $sheets,
                    'config_domain' => getenv('CONFIG_DOMAINE'),
                ])
                ->send();
        } catch (Exception $e) {
            error_log(sprintf(
                '[hlk-sheet-import] ⚠ échec envoi notification création (%d fiche(s)) : %s',
                count($sheets), $e->getMessage()
            ));
        }
    }
}
