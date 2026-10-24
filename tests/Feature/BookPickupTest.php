<?php

use App\Enums\ServiceLevel;
use App\Enums\ShipmentStatus;
use App\Enums\Zone;
use App\Mail\NewWebsiteRequest;
use App\Mail\ShipmentBooked;
use App\Models\Rate;
use App\Models\Setting;
use App\Models\Shipment;
use App\Support\Waybill;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
    Rate::factory()->create(['service_level' => ServiceLevel::Express, 'zone' => Zone::International, 'base_fee_cents' => 3500, 'per_kg_cents' => 1100, 'max_days' => 5]);
    Setting::put(['fuel_surcharge_percent' => 0, 'notify_email' => 'ops@northgate.example']);
});

/**
 * @return array<string, mixed>
 */
function pickupForm(array $overrides = []): array
{
    return [
        'sender_name' => 'Chiamaka Obi',
        'sender_email' => 'chiamaka@example.com',
        'sender_phone' => '+234 803 123 4567',
        'sender_address' => '12 Allen Avenue',
        'sender_city' => 'Lagos',
        'sender_country' => 'NG',
        'receiver_name' => 'Tom Hardy',
        'receiver_phone' => '+44 7700 900123',
        'receiver_address' => '3 High Street',
        'receiver_city' => 'London',
        'receiver_country' => 'GB',
        'contents' => 'Fashion samples',
        'pieces' => '1',
        'weight_kg' => '3',
        'service_level' => 'express',
        'pickup_date' => now()->addDay()->toDateString(),
        'agree' => true,
        ...$overrides,
    ];
}

function fillPickupForm(array $overrides = []): Testable
{
    $component = Livewire::test('pages::ship');

    foreach (pickupForm($overrides) as $field => $value) {
        $component->set($field, $value);
    }

    return $component;
}

test('booking a pickup creates a priced shipment and its first update', function () {
    fillPickupForm()->call('book')->assertHasNoErrors();

    $shipment = Shipment::query()->sole();

    expect($shipment->status)->toBe(ShipmentStatus::Booked)
        ->and($shipment->source)->toBe('website')
        ->and($shipment->service_level)->toBe(ServiceLevel::Express)
        ->and($shipment->price_cents)->toBe(3500 + 3 * 1100)
        ->and($shipment->tracking_number)->toMatch('/^NG\d{10}$/')
        ->and($shipment->estimated_delivery_date)->not->toBeNull()
        ->and($shipment->events()->sole()->status)->toBe(ShipmentStatus::Booked);
});

test('the sender gets the waybill and the office is told', function () {
    fillPickupForm()->call('book');

    Mail::assertSent(ShipmentBooked::class, fn ($mail) => $mail->hasTo('chiamaka@example.com') && count($mail->attachments()) === 1);
    Mail::assertSent(NewWebsiteRequest::class, fn ($mail) => $mail->hasTo('ops@northgate.example'));
});

test('after booking the visitor lands on a private confirmation page', function () {
    $component = fillPickupForm()->call('book');
    $shipment = Shipment::query()->sole();

    $component->assertRedirectContains('/ship/booked/'.$shipment->tracking_number);

    $this->get(URL::temporarySignedRoute('ship.booked', now()->addHour(), ['shipment' => $shipment->tracking_number]))
        ->assertOk()
        ->assertSee($shipment->tracking_number)
        ->assertSee('Download waybill');

    $this->get(route('ship.booked', $shipment->tracking_number))->assertForbidden();
});

test('the waybill PDF needs a signed link', function () {
    $shipment = Shipment::factory()->create();

    $this->get(Waybill::signedUrl($shipment))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->get(route('waybill', $shipment->tracking_number))->assertForbidden();
});

test('the form checks the details', function () {
    fillPickupForm(['sender_email' => 'not-an-email', 'weight_kg' => '0', 'pickup_date' => now()->subDay()->toDateString(), 'agree' => false])
        ->call('book')
        ->assertHasErrors(['sender_email', 'weight_kg', 'pickup_date', 'agree']);

    expect(Shipment::query()->count())->toBe(0);
});

test('a service with no rate on the route cannot be booked', function () {
    fillPickupForm(['service_level' => 'economy'])
        ->call('book')
        ->assertHasErrors('service_level');

    expect(Shipment::query()->count())->toBe(0);
});

test('spam bots that fill the hidden field are ignored', function () {
    fillPickupForm(['website' => 'http://spam.example'])->call('book')->assertRedirect(route('home'));

    expect(Shipment::query()->count())->toBe(0);
    Mail::assertNothingSent();
});

test('one connection can only book a few pickups in a row', function () {
    foreach (range(1, 5) as $attempt) {
        fillPickupForm()->call('book');
    }

    fillPickupForm()->call('book')->assertHasErrors('form');

    expect(Shipment::query()->count())->toBe(5);
});

test('the quote page link fills in the booking form', function () {
    $this->get(route('ship', ['from' => 'NG', 'to' => 'GB', 'weight' => '4', 'service' => 'express']))
        ->assertOk();

    Livewire::withQueryParams(['from' => 'GH', 'to' => 'US', 'weight' => '4', 'service' => 'economy'])
        ->test('pages::ship')
        ->assertSet('sender_country', 'GH')
        ->assertSet('receiver_country', 'US')
        ->assertSet('weight_kg', '4')
        ->assertSet('service_level', 'economy');
});
