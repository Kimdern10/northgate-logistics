<?php

namespace App\Models;

use App\Enums\ServiceLevel;
use App\Enums\Zone;
use Database\Factories\RateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * The price of one service level in one zone: a base fee plus a price per kilogram.
 *
 * @property int $id
 * @property ServiceLevel $service_level
 * @property Zone $zone
 * @property int $base_fee_cents
 * @property int $per_kg_cents
 * @property int $min_days
 * @property int $max_days
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['service_level', 'zone', 'base_fee_cents', 'per_kg_cents', 'min_days', 'max_days', 'is_active'])]
class Rate extends Model
{
    /** @use HasFactory<RateFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'service_level' => ServiceLevel::class,
            'zone' => Zone::class,
            'is_active' => 'boolean',
        ];
    }

    public function transitTime(): string
    {
        return $this->min_days === $this->max_days
            ? $this->min_days.' '.str('day')->plural($this->min_days)
            : "{$this->min_days}–{$this->max_days} days";
    }
}
