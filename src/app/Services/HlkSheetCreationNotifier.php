<?php

namespace App\Services;

use Core\Mailer\{Builder as Mailer, Exception};

/**
 * Notifie par email les fiches marché créées automatiquement depuis HLK
 * (statut Brouillon, à vérifier avant publication) — UN mail par acheteur,
 * listant les fiches créées pour lui lors du run (cf.
 * HlkSheetImportManager::run(), qui regroupe par acheteur_nni et appelle
 * notifyGroup() une fois par groupe).
 *
 * Résolution du destinataire (par ordre de priorité) :
 *   1. MAIL_HORSPROD_FICHES_A_PUBLIER si configurée — n'existe QUE dans les
 *      environnements non-prod (nom ET valeur absents en vraie prod) ;
 *      redirige TOUT vers cette adresse de test, quel que soit l'acheteur
 *      réel, pour ne jamais déranger de vraies personnes hors prod.
 *   2. L'email réel de l'acheteur (acheteur_mail, fourni par HLK).
 *   3. BAL_ACHATPEDIA_FICHES_A_PUBLIER en dernier recours — configurée en
 *      prod ET préprod, utilisée quand aucun email acheteur n'est connu pour
 *      la fiche (donnée HLK manquante).
 */
class HlkSheetCreationNotifier
{
    /**
     * @param array{
     *   acheteur_nni: string,
     *   acheteur: string,
     *   acheteur_mail: string,
     *   sheets: array<int, array{id:int, label:string, achat_id_cockpit:string}>
     * } $group Fiches créées pour UN acheteur lors du run. Ne fait rien si
     *   la liste de fiches est vide.
     */
    public function notifyGroup(array $group): void
    {
        $sheets = $group['sheets'] ?? [];
        if (empty($sheets)) {
            return;
        }

        $acheteurLabel = ($group['acheteur'] ?? '') !== '' ? $group['acheteur'] : '(acheteur inconnu)';
        $recipients    = $this->resolveRecipients((string) ($group['acheteur_mail'] ?? ''));
        if (empty($recipients)) {
            error_log(sprintf(
                '[hlk-sheet-import] notification ignorée (acheteur=%s, %d fiche(s)) : aucun destinataire résolu (ni email acheteur, ni BAL_ACHATPEDIA_FICHES_A_PUBLIER)',
                $acheteurLabel, count($sheets)
            ));
            return;
        }

        $configDomain = getenv('CONFIG_DOMAINE');

        try {
            if (count($sheets) === 1) {
                $sheet = $sheets[0];
                (new Mailer())
                    ->setSubject("[Achatpédia] Nouvelle fiche marché créée automatiquement : {$sheet['label']}")
                    ->setRecepients(array_fill_keys($recipients, ''))
                    ->html('mail/hlk_sheet_auto_created_single.html.twig', [
                        'label'            => $sheet['label'],
                        'achat_id_cockpit' => $sheet['achat_id_cockpit'],
                        'id'               => $sheet['id'],
                        'config_domain'    => $configDomain,
                    ])
                    ->send();
            } else {
                (new Mailer())
                    ->setSubject('[Achatpédia] Nouvelles fiches marchés créées automatiquement')
                    ->setRecepients(array_fill_keys($recipients, ''))
                    ->html('mail/hlk_sheet_auto_created.html.twig', [
                        'sheets'        => $sheets,
                        'config_domain' => $configDomain,
                    ])
                    ->send();
            }
        } catch (Exception $e) {
            error_log(sprintf(
                '[hlk-sheet-import] ⚠ échec envoi notification (acheteur=%s, %d fiche(s)) : %s',
                $acheteurLabel, count($sheets), $e->getMessage()
            ));
        }
    }

    /**
     * @return array<int,string> Liste d'adresses (vide si rien de résolu).
     */
    private function resolveRecipients(string $acheteurMail): array
    {
        // 1. Mode hors-prod : redirige tout, quel que soit l'acheteur réel.
        $horsProd = $this->splitRecipients(getenv('MAIL_HORSPROD_FICHES_A_PUBLIER'));
        if (!empty($horsProd)) {
            return $horsProd;
        }

        // 2. Email réel de l'acheteur, s'il est connu.
        $direct = $this->splitRecipients($acheteurMail);
        if (!empty($direct)) {
            return $direct;
        }

        // 3. Fallback : aucun email acheteur disponible (même en prod).
        return $this->splitRecipients(getenv('BAL_ACHATPEDIA_FICHES_A_PUBLIER'));
    }

    /**
     * Découpe une valeur en une ou plusieurs adresses séparées par des
     * virgules (ex: "a@edf.fr,b@edf.fr") — Builder::setRecepients() ne le
     * fait pas elle-même pour une string : elle la passerait telle quelle à
     * PHPMailer::addAddress(), qui n'accepte qu'UNE seule adresse.
     *
     * @param mixed $value getenv() renvoie false si la variable n'existe pas.
     * @return array<int,string>
     */
    private function splitRecipients(mixed $value): array
    {
        if (!is_string($value) || trim($value) === '') {
            return [];
        }
        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }
}
