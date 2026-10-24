<?php

use App\Enums\ServiceLevel;
use App\Enums\Zone;
use App\Models\Rate;
use App\Models\Setting;
use Livewire\Livewire;

beforeEach(function () {
    Setting::put(['fuel_surcharge_percent' => 0]);
    Rate::factory()->create(['service_level' => ServiceLevel::Standard, 'zone' => Zone::International, 'base_fee_cents' => 2000, 'per_kg_cents' => 700]);
    Rate::factory()->create(['service_level' => ServiceLevel::Express, 'zone' => Zone::Regional, 'base_fee_cents' => 2500, 'per_kg_cents' => 600]);
});

test('the calculator shows a price for each service on the route', function () {
    Livewire::test('pages::quote')
        ->set('fromCountry', 'NG')
        ->set('toCountry', 'GB')
        ->set('weight', '2')
        ->assertSee('Standard Air')
        ->assertSee('$34.00')
        ->assertDontSee('Express Air');
});

test('changing the route changes the prices', function () {
    Livewire::test('pages::quote')
        ->set('toCountry', 'GH')
        ->set('weight', '1')
        ->assertSee('Express Air')
        ->assertSee('$31.00');
});

test('a bad weight shows no prices', function () {
    Livewire::test('pages::quote')
        ->set('weight', '-3')
        ->assertHasErrors('weight')
        ->assertDontSee('Standard Air')
        ->assertSee('Fill in a valid weight');
});

test('the quote page opens from a link with the route filled in', function () {
    $this->get(route('quote', ['from' => 'NG', 'to' => 'GB', 'weight' => '2']))
        ->assertOk()
        ->assertSee('$34.00');
});
