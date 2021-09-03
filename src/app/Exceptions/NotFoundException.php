<?php

namespace App\Exceptions;

use Exception;

class NotFoundException extends Exception
{
    const CODE = 404;
    const MESSAGE = "not_found_error";

    public function __construct()
    {
        parent::__construct(self::MESSAGE, self::CODE);
    }
}