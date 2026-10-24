<?php

namespace App\Support;

/**
 * Prices are stored as whole cents and shown in US dollars.
 */
class Money
{
    public static function format(int $cents): string
    {
        return '$'.number_format($cents / 100, 2);
    }

    /**
     * Turn a typed amount such as "1,250.5" into cents.
     */
    public static function toCents(string|int|float|null $amount): int
    {
        return (int) round((float) str_replace(',', '', (string) $amount) * 100);
    }

    public static function toDecimal(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
