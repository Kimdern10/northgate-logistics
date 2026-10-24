<?php

use App\Enums\ServiceLevel;
use App\Enums\Zone;
use App\Models\Rate;
use App\Models\Setting;
use App\Services\RateCalculator;
use Carbon\CarbonImmutable;

beforeEach(function () {
    Rate::factory()->create(['service_level' => ServiceLevel::Standard, 'zone' => Zone::International, 'base_fee_cents' => 2000, 'per_kg_cents' => 700]);
    Setting::put(['fuel_surcharge_percent' => 8, 'volumetric_divisor' => 5000]);
});

test('the zone depends on whether the countries share a region', function (string $from, string $to, Zone $zone) {
    expect(RateCalculator::zoneFor($from, $to))->toBe($zone);
})->with([
    'same country' => ['NG', 'NG', Zone::Domestic],
    'same region' => ['NG', 'GH', Zone::Regional],
    'other region' => ['NG', 'GB', Zone::International],
    'unknown country' => ['NG', 'XX', Zone::International],
]);

test('price is base fee plus weight charge plus fuel surcharge', function () {
    $quote = app(RateCalculator::class)->quote(ServiceLevel::Standard, 'NG', 'GB', 2);

    expect($quote->chargeableWeightKg)->toBe(2.0)
        ->and($quote->weightChargeCents)->toBe(1400)
        ->and($quote->fuelSurchargeCents)->toBe(272)
        ->and($quote->totalCents())->toBe(3672)
        ->and($quote->total())->toBe('$36.72');
});

test('weight is rounded up to the next half kilo', function () {
    $quote = app(RateCalculator::class)->quote(ServiceLevel::Standard, 'NG', 'GB', 2.2);

    expect($quote->chargeableWeightKg)->toBe(2.5)
        ->and($quote->totalCents())->toBe(4050);
});

test('big light boxes are charged by size', function () {
    $quote = app(RateCalculator::class)->quote(ServiceLevel::Standard, 'NG', 'GB', 2, 50, 40, 30);

    expect($quote->volumetricWeightKg)->toBe(12.0)
        ->and($quote->chargeableWeightKg)->toBe(12.0)
        ->and($quote->totalCents())->toBe(11232);
});

test('switched off rates and other zones are not offered', function () {
    Rate::factory()->create(['service_level' => ServiceLevel::Express, 'zone' => Zone::International, 'is_active' => false]);
    Rate::factory()->create(['service_level' => ServiceLevel::Express, 'zone' => Zone::Domestic]);

    $levels = app(RateCalculator::class)->quotes('NG', 'GB', 1)->map(fn ($quote) => $quote->level)->all();

    expect($levels)->toBe([ServiceLevel::Standard]);
});

test('delivery date counts working days only', function () {
    $quote = app(RateCalculator::class)->quote(ServiceLevel::Standard, 'NG', 'GB', 1);

    // Friday + 7 working days = the Tuesday a week and a half later.
    expect($quote->estimatedDeliveryDate(CarbonImmutable::parse('2026-10-23'))->toDateString())->toBe('2026-11-03');
});
