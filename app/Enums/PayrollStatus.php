<?php

declare(strict_types=1);

namespace App\Enums;

enum PayrollStatus: string
{
    case Draft     = 'draft';
    case Processed = 'processed';
    case Paid      = 'paid';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Draft     => 'Draft',
            self::Processed => 'Processed',
            self::Paid      => 'Paid',
            self::Cancelled => 'Cancelled',
        };
    }
}
