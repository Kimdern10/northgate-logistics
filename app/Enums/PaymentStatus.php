<?php

namespace App\Enums;

enum PaymentStatus: string
{
    /** Not paid yet: the customer pays the courier at pickup, or has not finished paying online. */
    case Unpaid = 'unpaid';
    case Paid = 'paid';
    case Refunded = 'refunded';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Unpaid => 'amber',
            self::Paid => 'green',
            self::Refunded => 'zinc',
        };
    }
}
