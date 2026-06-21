<?php

declare(strict_types=1);

namespace App\Enums;

enum ExpenseStatus: string
{
    case Draft    = 'draft';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Paid     = 'paid';

    public function label(): string
    {
        return match($this) {
            self::Draft    => 'Draft',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Paid     => 'Paid',
        };
    }
}
