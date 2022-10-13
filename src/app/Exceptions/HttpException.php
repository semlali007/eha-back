<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace App\Exceptions;
use Core\Logger\{LoggerHandler as Logger, Level} ;

/**
 * Description of HttpException
 *
 * @author HGDDEDBN
 */
class HttpException extends \Exception{
    
    const CODE = \Core\Http\Response::HTTP_SERVICE_UNAVAILABLE;
    const MESSAGE = "Requete non valide !";
    
    /**
     * 
     */
    function __construct(string $message = self::MESSAGE, int|null $code = self::CODE) {
        $hlkMessage = "Message: not valid response ! HLK Service is down \n Code: {$code}" ;
        (new Logger(HLK_CHANNEL))->log($hlkMessage, Level::ERROR) ;
         parent::__construct($message,$code);
    }

   
}
