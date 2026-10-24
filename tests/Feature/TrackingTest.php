<?php

use App\Enums\ShipmentStatus;
use App\Models\Shipment;

function trackedShipment(): Shipment
{
    $shipment = Shipment::factory()->status(ShipmentStatus::InTransit)->create([
        'tracking_number' => 'NT1234567890',
        'receiver_name' => 'Adaora Chukwu Obi',
        'receiver_phone' => '+44 7700 900123',
        'receiver_address' => '221B Baker Street',
    ]);

    $shipment->events()->createMany([
        ['status' => ShipmentStatus::Booked, 'location' => 'Lagos, Nigeria', 'happened_at' => now()->subDays(2)],
        ['status' => ShipmentStatus::InTransit, 'location' => 'Lagos Airport Hub, Nigeria', 'description' => 'Departed on flight to London.', 'happened_at' => now()->subDay()],
    ]);

    return $shipment;
}

test('the tracking page shows the status and every update, newest first', function () {
    trackedShipment();

    $this->get(route('track.show', 'NT1234567890'))
        ->assertOk()
        ->assertSee('In transit')
        ->assertSeeInOrder(['Departed on flight to London.', 'Pickup booked'])
        ->assertSee('Lagos Airport Hub, Nigeria');
});

test('the public page does not show who the parcel is for or where it goes exactly', function () {
    trackedShipment();

    $this->get(route('track.show', 'NT1234567890'))
        ->assertSee('Adaora O.')
        ->assertDontSee('Adaora Chukwu Obi')
        ->assertDontSee('+44 7700 900123')
        ->assertDontSee('221B Baker Street');
});

test('the tracking form tidies the number and goes to the shipment page', function () {
    trackedShipment();

    $this->get(route('track', ['number' => ' nt 1234-567890 ']))
        ->assertRedirect(route('track.show', 'NT1234567890'));

    $this->get(route('track.show', 'nt1234567890'))->assertOk()->assertSee('NT1234567890');
});

test('an unknown tracking number says so with a 404', function () {
    $this->get(route('track.show', 'NT0000000000'))
        ->assertNotFound()
        ->assertSee("We couldn't find NT0000000000", false);
});

test('tracking lookups are rate limited', function () {
    foreach (range(1, 30) as $attempt) {
        $this->get(route('track.show', 'NT000000000'.($attempt % 10)));
    }

    $this->get(route('track.show', 'NT0000000001'))->assertTooManyRequests();
});
