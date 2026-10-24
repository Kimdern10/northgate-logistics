<?php

namespace App\Enums;

enum ServiceLevel: string
{
    case Express = 'express';
    case Standard = 'standard';
    /** Sea freight for heavy, non-urgent cargo. */
    case Economy = 'economy';

    public function label(): string
    {
        return match ($this) {
            self::Express => 'Express Air',
            self::Standard => 'Standard Air',
            self::Economy => 'Economy Freight',
        };
    }

    public function summary(): string
    {
        return match ($this) {
            self::Express => 'Fastest door-to-door, priority handling.',
            self::Standard => 'Reliable air delivery at a lower price.',
            self::Economy => 'Sea or road freight: best value for heavy cargo.',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $level): array => [$level->value => $level->label()])->all();
    }
}
