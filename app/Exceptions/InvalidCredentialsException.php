<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Thrown when an email/password pair does not match any active account.
 *
 * The message is intentionally vague to avoid leaking whether the email
 * address exists in the system (username-enumeration defence).
 */
final class InvalidCredentialsException extends AuthException
{
    public function __construct(
        string $message = 'The email address or password you entered is incorrect.',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
