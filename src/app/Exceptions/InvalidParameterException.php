<?php

namespace App\Exceptions;

use Exception;

class InvalidParameterException extends Exception 
{
    /**
     * Exception marketplace code.
     */
    const CODE = 403;
    const MESSAGE = "invalid_parameter";

    /**
     * @var string
     */
    private $parameterName;

    /**
     * @var array
     */
    private $errors;

    /**
     * @return array
     */
    public function __construct()
    {
        parent::__construct(self::MESSAGE, self::CODE);
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->parameterName ? [$this->parameterName => $this->errors] : $this->errors;
    }

    /**
     * @param array $errors
     *
     * @return InvalidParameterException
     */
    public function setErrors(array $errors): InvalidParameterException
    {
        $this->errors = $errors;

        return $this;
    }

    /**
     * @param string $parameterName
     *
     * @return InvalidParameterException
     */
    public function setParameterName(string $parameterName): InvalidParameterException
    {
        $this->parameterName = $parameterName;

        return $this;
    }

    /**
     * @return string
     */
    public function getParameterName(): string
    {
        return $this->parameterName;
    }
}
