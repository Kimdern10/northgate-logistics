<?php

use App\Enums\ShipmentStatus;
use App\Models\Shipment;

test('several tracking numbers show a summary of each', function () {
    Shipment::factory()->status(ShipmentStatus::InTransit)->create(['tracking_number' => 'NT1111111111']);
    Shipment::factory()->status(ShipmentStatus::Delivered)->create(['tracking_number' => 'NT2222222222']);

    $this->get(route('track', ['number' => 'nt1111111111, NT2222222222 NT9999999999']))
        ->assertOk()
        ->assertSeeInOrder(['NT1111111111', 'In transit', 'NT2222222222', 'Delivered', 'NT9999999999', 'Not found']);
});

test('the same number typed twice is shown once', function () {
    Shipment::factory()->create(['tracking_number' => 'NT1111111111']);

    $this->get(route('track', ['number' => 'NT1111111111,NT1111111111']))
        ->assertRedirect(route('track.show', 'NT1111111111'));
});

test('only ten numbers are looked up at once', function () {
    $numbers = collect(range(1, 12))->map(fn (int $index): string => 'NT'.str_pad((string) $index, 10, '0', STR_PAD_LEFT));

    $this->get(route('track', ['number' => $numbers->implode(' ')]))
        ->assertOk()
        ->assertSee('NT0000000010')
        ->assertDontSee('NT0000000011')
        ->assertSee('2 more numbers were left out');
});
