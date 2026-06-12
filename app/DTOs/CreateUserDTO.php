<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Entities\UserStatus;

/**
 * Data Transfer Object for creating a new user account.
 */
final class CreateUserDTO
{
    public function __construct(
        public readonly int $branchId,
        public readonly int $roleId,
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly ?string $phone,
        public readonly UserStatus $status,
        public readonly bool $sendWelcomeEmail,
    ) {}

    /**
     * Build from a raw request array (e.g. $_POST).
     */
    public static function fromArray(array $data): self
    {
        return new self(
            branchId:         (int) ($data['branch_id'] ?? 0),
            roleId:           (int) ($data['role_id'] ?? 0),
            name:             trim($data['name'] ?? ''),
            email:            strtolower(trim($data['email'] ?? '')),
            password:         $data['password'] ?? '',
            phone:            !empty($data['phone']) ? trim($data['phone']) : null,
            status:           UserStatus::from($data['status'] ?? UserStatus::Active->value),
            sendWelcomeEmail: filter_var($data['send_welcome_email'] ?? true, FILTER_VALIDATE_BOOLEAN),
        );
    }
}
