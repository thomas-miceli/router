<?php

namespace ThomasMiceli\Router\Exceptions;

use Exception;
use Throwable;

class NotFoundException extends Exception
{
    public function __construct($message = "", $code = 1, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
