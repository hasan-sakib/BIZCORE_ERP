<?php

declare(strict_types=1);

namespace App\Entities;

use DateTime;
use JsonSerializable;

/**
 * User Entity
 *
 * Immutable value object representing a user account.
 * All mutation produces a new instance via `with*()` helpers.
 */
final class User implements JsonSerializable
{
    public function __construct(
        public readonly int $id,
        public readonly int $branchId,
        public readonly int $roleId,
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $phone,
        public readonly ?string $avatar,
        public readonly UserStatus $status,
        public readonly ?DateTime $lastLoginAt,
        public readonly int $failedLoginAttempts,
        public readonly ?DateTime $lockedUntil,
        public readonly DateTime $createdAt,
        public readonly DateTime $updatedAt,
        public readonly array $permissions = [],
        public readonly ?string $roleSlug = null,
        public readonly ?string $roleName = null,
    ) {}

    // -------------------------------------------------------------------------
    // Factory
    // -------------------------------------------------------------------------

    /**
     * Construct a User from a raw associative array (e.g. PDO fetch result).
     */
    public static function fromArray(array $data): self
    {
        $rawPerms = $data['role_permissions'] ?? $data['permissions'] ?? null;
        $permissions = [];
        if (is_string($rawPerms) && $rawPerms !== '') {
            $decoded = json_decode($rawPerms, true);
            $permissions = is_array($decoded) ? $decoded : [];
        } elseif (is_array($rawPerms)) {
            $permissions = $rawPerms;
        }

        return new self(
            id:                   (int) $data['id'],
            branchId:             (int) $data['branch_id'],
            roleId:               (int) $data['role_id'],
            name:                 (string) $data['name'],
            email:                (string) $data['email'],
            phone:                isset($data['phone']) ? (string) $data['phone'] : null,
            avatar:               isset($data['avatar']) ? (string) $data['avatar'] : null,
            status:               UserStatus::from((string) $data['status']),
            lastLoginAt:          !empty($data['last_login_at'])
                                      ? new DateTime($data['last_login_at'])
                                      : null,
            failedLoginAttempts:  (int) ($data['failed_login_attempts'] ?? 0),
            lockedUntil:          !empty($data['locked_until'])
                                      ? new DateTime($data['locked_until'])
                                      : null,
            createdAt:            new DateTime($data['created_at']),
            updatedAt:            new DateTime($data['updated_at']),
            permissions:          $permissions,
            roleSlug:             isset($data['role_slug']) ? (string) $data['role_slug'] : null,
            roleName:             isset($data['role_name']) ? (string) $data['role_name'] : null,
        );
    }

    // -------------------------------------------------------------------------
    // Domain logic
    // -------------------------------------------------------------------------

    /**
     * Returns true when the account is currently locked out.
     */
    public function isLocked(): bool
    {
        if ($this->status === UserStatus::Locked) {
            return true;
        }

        if ($this->lockedUntil !== null && $this->lockedUntil > new DateTime()) {
            return true;
        }

        return false;
    }

    /**
     * Returns true when the account is active and not locked.
     */
    public function isActive(): bool
    {
        return $this->status === UserStatus::Active && !$this->isLocked();
    }

    /**
     * Returns the URL to the user's avatar, falling back to a generated
     * identicon URL when no custom avatar has been uploaded.
     */
    public function avatarUrl(string $baseUrl = ''): string
    {
        if ($this->avatar !== null) {
            if (str_starts_with($this->avatar, 'https://') || str_starts_with($this->avatar, 'http://')) {
                return $this->avatar;
            }
            return rtrim($baseUrl, '/') . '/storage/avatars/' . $this->avatar;
        }

        $hash = md5(strtolower(trim($this->email)));
        return "https://www.gravatar.com/avatar/{$hash}?d=identicon&s=128";
    }

    /**
     * Returns a display-friendly first name.
     */
    public function firstName(): string
    {
        return explode(' ', $this->name, 2)[0];
    }

    // -------------------------------------------------------------------------
    // Serialisation
    // -------------------------------------------------------------------------

    /**
     * Convert entity to a plain associative array.
     */
    public function toArray(): array
    {
        return [
            'id'                    => $this->id,
            'branch_id'             => $this->branchId,
            'role_id'               => $this->roleId,
            'name'                  => $this->name,
            'email'                 => $this->email,
            'phone'                 => $this->phone,
            'avatar'                => $this->avatar,
            'status'                => $this->status->value,
            'status_label'          => $this->status->label(),
            'last_login_at'         => $this->lastLoginAt?->format('Y-m-d H:i:s'),
            'failed_login_attempts' => $this->failedLoginAttempts,
            'locked_until'          => $this->lockedUntil?->format('Y-m-d H:i:s'),
            'is_locked'             => $this->isLocked(),
            'is_active'             => $this->isActive(),
            'created_at'            => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at'            => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
