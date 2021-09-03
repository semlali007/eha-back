<?php

namespace App\Exceptions;

use Exception;

/**
 * Class AccessDeniedException.
 */
class AccessDeniedException extends Exception
{
    /**
     * Exception marketplace code.
     */
    const CODE = 504;
    const MESSAGE = "user_access_denied";
    /**
     * @return array
     */
    public function __construct()
    {
        parent::__construct(self::MESSAGE, self::CODE);
    }
}
