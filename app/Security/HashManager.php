<?php

declare(strict_types=1);

namespace App\Security;

class HashManager
{
    public function __construct(
        private readonly int $algo   = PASSWORD_BCRYPT,
        private readonly int $rounds = 12,
    ) {}

    public function make(string $value): string
    {
        return (string) password_hash($value, $this->algo, ['cost' => $this->rounds]);
    }

    public function check(string $value, string $hash): bool
    {
        return password_verify($value, $hash);
    }

    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, $this->algo, ['cost' => $this->rounds]);
    }
}
