<?php

declare(strict_types=1);

namespace App\Enums;

enum UserStatus: string
{
    case Active   = 'active';
    case Inactive = 'inactive';
    case Locked   = 'locked';

    public function label(): string
    {
        return match($this) {
            self::Active   => 'Active',
            self::Inactive => 'Inactive',
            self::Locked   => 'Locked',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Active   => 'badge-success',
            self::Inactive => 'badge-secondary',
            self::Locked   => 'badge-danger',
        };
    }
}
