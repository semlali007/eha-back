<?php

namespace App\Services;

use Core\Mailer\{Builder as mailer, Exception };

/**
 * ArchivageService
 *
 * @author HGDDEDBN
 */
class ArchivageService {
    
    public function sendMailNotification(array $fiches = []) : void {
        try {
            $mailer = new mailer() ;
            $mailer // instancier le mailer class
                ->setSubject("Notification automatique d'expiration des fiches") // mettre le sujet
                ->setFrom("eha@edf.fr", "Achatpédia.fr")
                ->setRecepients(getenv("BAL_FICHE_MARCHE"))
                // le fichier doit etre dans le dossier /templates 
                ->html("mail/auto_archivage.html.twig",["fiches" => $fiches]) // Va chercher une variable dans le template de type {{key}} (delimiter ##VARIABLE##)
               ;
           // $mailer ->send() ; // Envoyer l'email
        
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mailer->getErrorInfo()}";
        }


}
