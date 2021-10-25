<?php
 
namespace Core\Error;

/**
 * Description of ErrorManager
 *
 * @author HGDDEDBN
 */
class ErrorManager {

    /**
     * Activer la gestion des erreur
     * @return void
     */
    public static function enable(): void {
        set_error_handler('Core\Error\ErrorHandler::errorHandler');
        set_exception_handler('Core\Error\ErrorHandler::exceptionHandler');
    }

}
