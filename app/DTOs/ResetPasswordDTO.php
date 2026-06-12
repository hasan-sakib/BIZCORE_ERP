<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Data Transfer Object for a password-reset request.
 */
final class ResetPasswordDTO
{
    public function __construct(
        public readonly string $token,
        public readonly string $email,
        public readonly string $password,
        public readonly string $passwordConfirmation,
    ) {}

    /**
     * Build from a raw request array (e.g. $_POST).
     */
    public static function fromArray(array $data): self
    {
        return new self(
            token:                trim($data['token'] ?? ''),
            email:                strtolower(trim($data['email'] ?? '')),
            password:             $data['password'] ?? '',
            passwordConfirmation: $data['password_confirmation'] ?? '',
        );
    }

    /**
     * Returns true when the password and its confirmation match.
     */
    public function passwordsMatch(): bool
    {
        return $this->password === $this->passwordConfirmation;
    }
}
