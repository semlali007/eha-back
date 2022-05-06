<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace App\Exceptions;


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
         parent::__construct($message,$code);
    }

   
}
