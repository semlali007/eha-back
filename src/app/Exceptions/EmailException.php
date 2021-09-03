<?php

namespace App\Exceptions;

use Exception;

class EmailException extends Exception
{
    const CODE = 500;
    const MESSAGE = "email_exception";
    public function __construct($message)
    {
        parent::__construct($message, self::CODE);
    }
}
