<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Base exception for all authentication-related errors.
 */
class AuthException extends RuntimeException
{
    public function __construct(
        string $message = 'Authentication error.',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
