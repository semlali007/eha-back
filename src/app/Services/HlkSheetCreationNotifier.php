<?php

namespace App\Services;

use Core\Mailer\{Builder as Mailer, Exception};

/**
 * Notifie par email la BAL fonctionnelle Achatpédia lorsqu'une fiche marché
 * est créée automatiquement depuis HLK (statut Brouillon, à vérifier avant
 * publication).
 *
 * Il n'existe aucun moyen fiable de résoudre l'email personnel de l'acheteur
 * (le champ `acheteur` d'une fiche est un texte libre venant de HLK, sans lien
 * vers un référentiel utilisateur) — décision métier : la notification part
 * uniquement vers la BAL fonctionnelle configurée.
 */
class HlkSheetCreationNotifier
{
    /**
     * @param int    $sheetId        Id de la fiche EHA nouvellement créée
     * @param string $label          Libellé de la fiche marché
     * @param string $achatIdCockpit Référence Cockpit du marché
     */
    public function notify(int $sheetId, string $label, string $achatIdCockpit): void
    {
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
                ->setSubject("[Achatpédia] Nouvelle fiche marché créée automatiquement : {$label}")
                ->setRecepients(array_fill_keys($recipients, ''))
                ->html('mail/hlk_sheet_auto_created.html.twig', [
                    'label'            => $label,
                    'achat_id_cockpit' => $achatIdCockpit,
                    'id'               => $sheetId,
                    'config_domain'    => getenv('CONFIG_DOMAINE'),
                ])
                ->send();
        } catch (Exception $e) {
            error_log("[hlk-sheet-import] ⚠ échec envoi notification création fiche sheet_id={$sheetId} : " . $e->getMessage());
        }
    }
}
