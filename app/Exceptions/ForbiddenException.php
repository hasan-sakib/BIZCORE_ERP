<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an authenticated user attempts an action they are not
 * authorised to perform (HTTP 403 Forbidden).
 */
final class ForbiddenException extends RuntimeException
{
    public function __construct(
        string $message = 'You do not have permission to perform this action.',
        int $code = 403,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
