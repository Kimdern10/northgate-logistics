<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Once;

/**
 * Key/value store for business details, home page text and pricing extras.
 *
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['key', 'value'])]
class Setting extends Model
{
    /**
     * Values used until the owner saves their own.
     *
     * @var array<string, string>
     */
    public const array DEFAULTS = [
        'business_name' => 'Northgate Logistics',
        'tagline' => 'International shipping, warehousing and e-commerce fulfillment, tracked door to door.',
        'email' => 'hello@northgate.example',
        'phone' => '+1 (800) 555-0142',
        'whatsapp' => '+1 (212) 555-0186',
        'address' => '1200 Harbor Point Blvd, Suite 400, Jersey City, NJ 07310, USA',
        'notify_email' => 'ops@northgate.example',
        'hero_title' => 'Ship anywhere. Track every mile.',
        'hero_text' => 'Air and ocean freight from the USA to 40+ countries, secure warehousing and same-day order fulfillment for growing brands.',
        'stat_years' => '12',
        'stat_countries' => '42',
        'stat_deliveries' => '250000',
        'stat_warehouse_sqm' => '200000',
        'fuel_surcharge_percent' => '8',
        'volumetric_divisor' => '5000',
    ];

    private const string CACHE_KEY = 'site-settings';

    /**
     * Get every stored value, falling back to the defaults.
     *
     * @return array<string, string>
     */
    public static function values(): array
    {
        // Read once per request; defaults go on top so new keys are never missing from an old cache.
        return once(fn (): array => array_merge(self::DEFAULTS, Cache::rememberForever(
            self::CACHE_KEY,
            fn (): array => static::query()->pluck('value', 'key')->map(fn (?string $value): string => (string) $value)->all(),
        )));
    }

    public static function get(string $key): string
    {
        return self::values()[$key] ?? '';
    }

    public static function int(string $key): int
    {
        return (int) self::get($key);
    }

    /**
     * Save several values at once.
     *
     * @param  array<string, string|int|float|null>  $values
     */
    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            static::query()->updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }

        Cache::forget(self::CACHE_KEY);
        Once::flush();
    }
}
