<?php

declare(strict_types=1);

namespace App\Enums;

enum AttendanceStatus: string
{
    case Present  = 'present';
    case Absent   = 'absent';
    case HalfDay  = 'half_day';
    case Late     = 'late';
    case OnLeave  = 'on_leave';
    case Holiday  = 'holiday';

    public function label(): string
    {
        return match($this) {
            self::Present  => 'Present',
            self::Absent   => 'Absent',
            self::HalfDay  => 'Half Day',
            self::Late     => 'Late',
            self::OnLeave  => 'On Leave',
            self::Holiday  => 'Holiday',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Present  => 'badge-success',
            self::Absent   => 'badge-danger',
            self::HalfDay  => 'badge-warning',
            self::Late     => 'badge-warning',
            self::OnLeave  => 'badge-info',
            self::Holiday  => 'badge-secondary',
        };
    }
}
