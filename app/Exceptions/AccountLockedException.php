<?php

declare(strict_types=1);

namespace App\Exceptions;

use DateTime;

/**
 * Thrown when a login attempt is made against a locked account.
 */
final class AccountLockedException extends AuthException
{
    public function __construct(
        private readonly ?DateTime $lockedUntil = null,
        string $message = 'Your account has been locked due to too many failed login attempts.',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * The date-time when the lock will be automatically lifted.
     * Null if the lock is indefinite (manual admin unlock required).
     */
    public function getLockedUntil(): ?DateTime
    {
        return $this->lockedUntil;
    }

    /**
     * Returns the remaining lock duration in minutes, rounded up.
     */
    public function getRemainingMinutes(): int
    {
        if ($this->lockedUntil === null) {
            return 0;
        }

        $diff = (new DateTime())->diff($this->lockedUntil);
        $totalSeconds = ($diff->days * 86400) + ($diff->h * 3600) + ($diff->i * 60) + $diff->s;

        return (int) ceil($totalSeconds / 60);
    }
}
