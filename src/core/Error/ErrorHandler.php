<?php

namespace Core\Error;

use Core\Logger\{LoggerHandler, Level} ;

/**
 * Description of ErrorHandler
 *
 * @author HGDDEDBN
 */
class ErrorHandler {

    const HTML_MSG = 0; // Html Message
    const TXT_MSG = 1; // Text Message 
    const DEFAULT_CHANNEL = "APP";
    // Message par defaut
    const DEFAULT_MSG_PROD = "Le site est en cours de mise à jour, merci de revenir ulterieurment...";

    /*  Handle error Methode
      =========================================== */

    /*    
     *
     * @param int $level  Error level
     * @param string $message Error message
     * @param string $file  Filename the error was raised in
     * @param int $line  Line number in the file
     *
     * @return void
     */

    public static function errorHandler($level, $message, $file, $line) {

        if ((bool) $_ENV["DEV_MODE"] == true && error_reporting() !== 0) {  // to keep the @ operator working
            throw new \ErrorException($message, 0, $level, $file, $line);
        }
    }

    /**
     * 
     * @param \Exception $exception
     */
    public static function exceptionHandler($exception): void {

        http_response_code($exception->getCode()); // retourner le http status code 
        $handler = LoggerHandler::getInstance(self::DEFAULT_CHANNEL);

        // ecrire les logs seulement dans l'environnement de DEV
        $handler->log(self::generateMessageError($exception, true), Level::ERROR);

        // Affichage message d'erreur sous forme HTML
        print self::generateMessageError($exception, self::HTML_MSG, false);
    }

    /**
     * 
     *  Reçoit un argument pour le type de sortie.
     *  Les lignes sont séparées par \n ou <BR> (basé sur l'argument $output).
     * 
     * @param \Exception $exception
     * @param int $msgType
     * @return null|string
     */
    private static function generateMessageError($exception, bool $forceCatchError = false, int $msgType = self::TXT_MSG): ?string {

        if ((true === $forceCatchError) || (isset($_ENV["DEV_MODE"]) && filter_var($_ENV["DEV_MODE"], FILTER_VALIDATE_BOOLEAN))):
            $message = "Error:";
            $message .= "Uncaught exception: " . get_class($exception) . " \n";
            $message .= "Message:" . $exception->getMessage() . " \n";
            $message .= "Stack trace:" . $exception->getTraceAsString() . " \n";
            $message .= "Thrown in '" . $exception->getFile() . " on line " . $exception->getLine();
        else:
            $message = "code: " . $exception->getCode() . " \n";
            $message .= "Message: " . $exception->getMessage() . " \n";
            $message .= self::DEFAULT_MSG_PROD;
        endif;

        return ($msgType === self::TXT_MSG) ? $message : nl2br($message);
    }

}
