<?php

use App\Enums\ShipmentStatus;
use App\Models\Shipment;

test('several tracking numbers show a summary of each', function () {
    Shipment::factory()->status(ShipmentStatus::InTransit)->create(['tracking_number' => 'NG1111111111']);
    Shipment::factory()->status(ShipmentStatus::Delivered)->create(['tracking_number' => 'NG2222222222']);

    $this->get(route('track', ['number' => 'ng1111111111, NG2222222222 NG9999999999']))
        ->assertOk()
        ->assertSeeInOrder(['NG1111111111', 'In transit', 'NG2222222222', 'Delivered', 'NG9999999999', 'Not found']);
});

test('the same number typed twice is shown once', function () {
    Shipment::factory()->create(['tracking_number' => 'NG1111111111']);

    $this->get(route('track', ['number' => 'NG1111111111,NG1111111111']))
        ->assertRedirect(route('track.show', 'NG1111111111'));
});

test('only ten numbers are looked up at once', function () {
    $numbers = collect(range(1, 12))->map(fn (int $index): string => 'NG'.str_pad((string) $index, 10, '0', STR_PAD_LEFT));

    $this->get(route('track', ['number' => $numbers->implode(' ')]))
        ->assertOk()
        ->assertSee('NG0000000010')
        ->assertDontSee('NG0000000011')
        ->assertSee('2 more numbers were left out');
});
