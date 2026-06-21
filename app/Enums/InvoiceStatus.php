<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft     = 'draft';
    case Sent      = 'sent';
    case Partial   = 'partial';
    case Paid      = 'paid';
    case Overdue   = 'overdue';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Draft     => 'Draft',
            self::Sent      => 'Sent',
            self::Partial   => 'Partial',
            self::Paid      => 'Paid',
            self::Overdue   => 'Overdue',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Draft     => 'badge-secondary',
            self::Sent      => 'badge-primary',
            self::Partial   => 'badge-warning',
            self::Paid      => 'badge-success',
            self::Overdue   => 'badge-danger',
            self::Cancelled => 'badge-dark',
        };
    }

    public function isUnpaid(): bool
    {
        return in_array($this, [self::Draft, self::Sent, self::Partial, self::Overdue]);
    }
}
