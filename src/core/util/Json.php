<?php

namespace Core\Util;

use Core\Http\Response;

/**
 * Description of Json
 *
 * @author HGDDEDBN
 */
class Json {

    const DEFAULT_HEADER = ['Content-Type: application/json','Accept-Charset: utf-8'] ;
    /**
     * @param array $message
     * @param int $statusCode
     */
    public static function createError(array $message, int $statusCode = Response::HTTP_BAD_REQUEST): ?array {
        if (Response::isValid($statusCode)):  // Ajout http ResponseCode
            $message["status"] = Response::getMessageFromCode($statusCode);
        endif;

        header('Content-Type: application/json');
        http_response_code($statusCode); // Modifier le code de la réponse
        
        return self::toJson($message);
    }
    
    /**
     * 
     * @param string $message
     * @return array
     */
    public static function toJson(mixed $message):mixed
    {
        return json_encode($message) ;
    }
    
    

}
