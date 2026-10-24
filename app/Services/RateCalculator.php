<?php

namespace App\Services;

use App\Enums\ServiceLevel;
use App\Enums\Zone;
use App\Models\Rate;
use App\Models\Setting;
use App\Support\Countries;
use Illuminate\Support\Collection;

/**
 * Prices a shipment from the rates the owner sets in the admin.
 *
 * Price = base fee + (price per kg × chargeable weight) + fuel surcharge.
 * Chargeable weight is the larger of the real weight and the volumetric weight
 * (length × width × height ÷ divisor), rounded up to the next half kilo.
 */
class RateCalculator
{
    public static function zoneFor(string $fromCountry, string $toCountry): Zone
    {
        if (strtoupper($fromCountry) === strtoupper($toCountry)) {
            return Zone::Domestic;
        }

        $fromRegion = Countries::region($fromCountry);

        return $fromRegion !== null && $fromRegion === Countries::region($toCountry)
            ? Zone::Regional
            : Zone::International;
    }

    public static function volumetricWeight(?int $lengthCm, ?int $widthCm, ?int $heightCm): float
    {
        if (! $lengthCm || ! $widthCm || ! $heightCm) {
            return 0.0;
        }

        $divisor = max(1, Setting::int('volumetric_divisor') ?: 5000);

        return round($lengthCm * $widthCm * $heightCm / $divisor, 2);
    }

    /**
     * Prices for every service level offered on this route, fastest first.
     *
     * @return Collection<int, Quote>
     */
    public function quotes(string $fromCountry, string $toCountry, float $weightKg, ?int $lengthCm = null, ?int $widthCm = null, ?int $heightCm = null): Collection
    {
        $zone = self::zoneFor($fromCountry, $toCountry);

        return Rate::query()
            ->where('zone', $zone)
            ->where('is_active', true)
            ->get()
            ->sortBy(fn (Rate $rate): int => array_search($rate->service_level, ServiceLevel::cases(), true))
            ->map(fn (Rate $rate): Quote => $this->price($rate, $weightKg, $lengthCm, $widthCm, $heightCm))
            ->values();
    }

    public function quote(ServiceLevel $level, string $fromCountry, string $toCountry, float $weightKg, ?int $lengthCm = null, ?int $widthCm = null, ?int $heightCm = null): ?Quote
    {
        return $this->quotes($fromCountry, $toCountry, $weightKg, $lengthCm, $widthCm, $heightCm)
            ->first(fn (Quote $quote): bool => $quote->level === $level);
    }

    private function price(Rate $rate, float $weightKg, ?int $lengthCm, ?int $widthCm, ?int $heightCm): Quote
    {
        $volumetric = self::volumetricWeight($lengthCm, $widthCm, $heightCm);
        $chargeable = max(0.5, ceil(max($weightKg, $volumetric) * 2) / 2);

        $weightCharge = (int) round($rate->per_kg_cents * $chargeable);
        $fuel = (int) round(($rate->base_fee_cents + $weightCharge) * max(0, (float) Setting::get('fuel_surcharge_percent')) / 100);

        return new Quote(
            level: $rate->service_level,
            zone: $rate->zone,
            actualWeightKg: $weightKg,
            volumetricWeightKg: $volumetric,
            chargeableWeightKg: $chargeable,
            baseFeeCents: $rate->base_fee_cents,
            weightChargeCents: $weightCharge,
            fuelSurchargeCents: $fuel,
            minDays: $rate->min_days,
            maxDays: $rate->max_days,
        );
    }
}
