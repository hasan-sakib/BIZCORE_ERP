<?php

declare(strict_types=1);

namespace App\Exceptions;

use Carbon\Carbon;

class AccountLockedException extends AuthException
{
    public function __construct(
        private readonly ?Carbon $lockedUntil = null,
        string $message = ''
    ) {
        parent::__construct($message ?: $this->buildMessage());
    }

    public function getRemainingMinutes(): int
    {
        if ($this->lockedUntil === null) {
            return 0;
        }
        return (int) max(0, now()->diffInMinutes($this->lockedUntil, false));
    }

    private function buildMessage(): string
    {
        $minutes = $this->getRemainingMinutes();
        return $minutes > 0
            ? "Your account is locked. Try again in {$minutes} minute(s)."
            : 'Your account is locked. Please contact an administrator.';
    }
}
