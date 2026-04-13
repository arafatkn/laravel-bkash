<?php

namespace Arafatkn\LaravelBkash\Exceptions;

use Exception;

class TokenGrantException extends Exception
{
    public function __construct(string $message = 'Failed to grant bKash token.')
    {
        parent::__construct($message);
    }
}
