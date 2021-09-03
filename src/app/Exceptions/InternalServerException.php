<?php

namespace App\Exceptions;

use Exception;

/**
 * Class AccessDeniedException.
 */
class InternalServerException extends \Exception
{
    /**
     * Exception marketplace code.
     */

    const CODE = 500;
    const MESSAGE = "internal_server_error";

    /**
     * @return array
     */
    public function __construct($message = '')
    {
        if($message == ''){
            $message = self::MESSAGE;
        }
        parent::__construct($message, self::CODE);
    }
}
