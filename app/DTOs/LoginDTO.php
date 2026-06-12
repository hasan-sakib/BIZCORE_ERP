<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Data Transfer Object for a login request.
 */
final class LoginDTO
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly bool $remember,
        public readonly string $ipAddress,
        public readonly string $userAgent,
    ) {}

    /**
     * Build from a raw request array (e.g. $_POST + $_SERVER).
     */
    public static function fromArray(array $data): self
    {
        return new self(
            email:     strtolower(trim($data['email'] ?? '')),
            password:  $data['password'] ?? '',
            remember:  filter_var($data['remember'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ipAddress: $data['ip_address'] ?? '',
            userAgent: $data['user_agent'] ?? '',
        );
    }
}
