<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class HotlineException extends Exception {

   public $messages = array(
        '01' => 'Désolé, le captcha saisi est incorrect.',
        '02' => 'Au moins une des informations transmises est incorrecte',
        '04' => 'Aucun hotliner associé à cette catégorie.',
        '05' => 'Vous pouvez à tout moment retrouver votre question en cliquant sur le lien suivant :',
        '06' => 'Vous avez une nouvelle question',
        '07' => 'Votre question a été enregistrée sous le numéro :',
        '08' => 'Un mail de confirmation vous a été adressé contenant un lien pour accéder au fil de discussion relatif à votre question.',
        '09' => 'Désolé, lors de l\'envoi d\'un mail, le serveur a rencontré une erreur inattendue.',
        '10' => 'Désolé, votre demande ne peut pas aboutir car soit la discussion est fermée ou bien les informations fournies sont incorrectes.',
        '11' => 'Désolé, nous avons rencontré une erreur côté serveur.',
        '12' => 'Un collègue vient de vous transférer une question. Merci d\'y répondre en cliquant sur le lien suivant :',
        '13' => 'Un collègue vient de vous transférer une question posée à la Direction des Achats Groupe. Vous pouvez y accéder en cliquant sur le lien suivant :',
        '14' => 'Désolé, le serveur a rencontré une erreur dans l\'envoi d\'un mail.',
        '15' => 'Transfert effectué avec succès.',
        '16' => 'Désolé, cette discussion est inconnue.',
        '17' => 'Désolé, le serveur a rencontré une erreur en voulant sauvegarder votre message.',
        '18' => 'La hotline de la DAG vient de poster un nouveau message relatif à votre question. Merci de cliquer sur le lien suivant pour y accéder :',
        '19' => 'Le demandeur vient de poster un nouveau message relatif à sa question. Merci de cliquer sur le lien suivant pour y accéder :',
        '20' => 'Votre message a bien été pris en compte.',
        '21' => 'Le demandeur vient de poster un nouveau message relatif à sa question. Merci de cliquer sur le lien suivant :',
        '22' => 'Désolé, la hotline ne peut clore manuellement une discussion, seul le demandeur peut clore manuellement une discussion.',
        '23' => 'Désolé, le serveur a rencontré une erreur en voulant fermer cette discussion.',
        '24' => 'Une discussion vient d\'être fermée, pour la consulter, cliquer sur le lien suivant :',
        '25' => 'Votre message a bien été pris en compte.',
        '26' => 'Cette discussion a bien été fermée.',
        '27' => 'Aucune suggestion.',
        '28' => 'Vous allez recevoir un email avec un lien vous permettant d\'intervenir dans cet échange.',
        '29' => 'Désolé, il semblerait que vous ne puissiez pas intervenir dans cet échange.',
        '30' => 'Ci-après le lien qui vous permettra d\'intervenir dans cet échange :',
        '31' => 'Désolé, le serveur a rencontré un problème en récupérant la pièce jointe.',
        '32' => 'Désolé, votre fichier est trop volumineux, sa taille doit être inférieure à 1 Mo.',
        '33' => 'Désolé, le serveur a rencontré un problème technique en voulant enregistrer la pièce jointe.',
        '34' => 'Pièce jointe ajoutée à la discussion.',
        '35' => 'Pièce jointe ajoutée à la discussion, mais email d\'information non transmis.',
    );

    public function __construct($message = "", $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function forHotline($message = '')
    {
        return new self($message);
    }
}
