<?php

namespace App\Exceptions;

class InvalidTokenException extends \Exception implements AuthenticationException
{
    /**
     * @var \Exception
     */
    protected $previousException;

    public function getPreviousException(): \Exception
    {
        return $this->previousException;
    }

    public function setPreviousException(\Exception $previousException): void
    {
        $this->previousException = $previousException;
    }
}
