<?php

namespace ThomasMiceli\Router\Exceptions;

use Exception;
use Throwable;

class EmptyResponseException extends Exception
{
    public function __construct($message = "Responses or middleware handlers have to be returned by the function calling them", $code = 2, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
