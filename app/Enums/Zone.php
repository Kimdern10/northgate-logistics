<?php

namespace App\Enums;

/**
 * How far a parcel travels, which sets its rate.
 */
enum Zone: string
{
    /** Same country. */
    case Domestic = 'domestic';
    /** Another country in the same region, e.g. USA to Canada. */
    case Regional = 'regional';
    /** Another region, e.g. USA to the UK. */
    case International = 'international';

    public function label(): string
    {
        return match ($this) {
            self::Domestic => 'Domestic (same country)',
            self::Regional => 'Regional (same region)',
            self::International => 'International (other regions)',
        };
    }
}
