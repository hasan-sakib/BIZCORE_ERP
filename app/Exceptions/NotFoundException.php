<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a requested resource cannot be found.
 */
final class NotFoundException extends RuntimeException
{
    public function __construct(
        string $resource = 'Resource',
        int|string|null $id = null,
        int $code = 404,
        ?\Throwable $previous = null,
    ) {
        $message = $id !== null
            ? "{$resource} with identifier '{$id}' was not found."
            : "{$resource} was not found.";

        parent::__construct($message, $code, $previous);
    }
}
