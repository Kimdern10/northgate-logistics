<?php

namespace App\Enums;

enum EnquiryStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Won = 'won';
    case Closed = 'closed';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'amber',
            self::Contacted => 'blue',
            self::Won => 'green',
            self::Closed => 'zinc',
        };
    }
}
