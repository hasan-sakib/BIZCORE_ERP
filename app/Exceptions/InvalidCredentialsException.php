<?php

declare(strict_types=1);

namespace App\Exceptions;

class InvalidCredentialsException extends AuthException
{
    public function __construct(string $message = 'Invalid email or password.')
    {
        parent::__construct($message);
    }
}
