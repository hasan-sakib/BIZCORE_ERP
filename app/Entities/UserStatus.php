<?php

declare(strict_types=1);

namespace App\Entities;

/**
 * UserStatus Enum
 *
 * Represents the possible status values for a user account.
 */
enum UserStatus: string
{
    case Active   = 'active';
    case Inactive = 'inactive';
    case Locked   = 'locked';

    /**
     * Return a human-readable label for display.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active   => 'Active',
            self::Inactive => 'Inactive',
            self::Locked   => 'Locked',
        };
    }

    /**
     * Returns the CSS badge class associated with this status.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Active   => 'badge-success',
            self::Inactive => 'badge-secondary',
            self::Locked   => 'badge-danger',
        };
    }
}
