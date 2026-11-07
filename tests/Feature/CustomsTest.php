<?php

use App\Enums\ServiceLevel;
use App\Enums\Zone;
use App\Models\Rate;
use App\Models\Setting;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\User;
use App\Support\CommercialInvoice;
use Illuminate\Support\Facades\Mail;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
    Rate::factory()->create(['service_level' => ServiceLevel::Express, 'zone' => Zone::International]);
    Rate::factory()->create(['service_level' => ServiceLevel::Express, 'zone' => Zone::Domestic]);
});

function bookingTo(string $country): Testable
{
    return Livewire::test('pages::ship')
        ->set('sender_name', 'Megan Carter')
        ->set('sender_email', 'megan@example.com')
        ->set('sender_phone', '+1 (212) 555-0100')
        ->set('sender_address', '120 W 34th St')
        ->set('sender_city', 'New York')
        ->set('sender_country', 'US')
        ->set('receiver_name', 'Tom Hardy')
        ->set('receiver_phone', '+44 7700 900123')
        ->set('receiver_address', '3 High Street')
        ->set('receiver_city', $country === 'US' ? 'Boston' : 'London')
        ->set('receiver_country', $country)
        ->set('contents', 'Clothing')
        ->set('weight_lb', '4')
        ->set('service_level', 'express')
        ->set('agree', true);
}

test('a parcel within the same country needs no customs details', function () {
    bookingTo('US')
        ->assertDontSee('Customs details')
        ->call('book')
        ->assertHasNoErrors();

    expect(Shipment::query()->sole()->items()->count())->toBe(0);
});

test('a parcel crossing a border asks for customs lines and saves them', function () {
    bookingTo('GB')
        ->assertSee('Customs details')
        ->call('book')
        ->assertHasErrors(['items.0.description', 'items.0.unit_value']);

    bookingTo('GB')
        ->set('export_reason', 'gift')
        ->set('items.0.description', 'Cotton T-shirts')
        ->set('items.0.quantity', '3')
        ->set('items.0.unit_value', '12.50')
        ->call('addItem')
        ->set('items.1.description', 'Paperback books')
        ->set('items.1.quantity', '2')
        ->set('items.1.unit_value', '8')
        ->set('items.1.origin_country', 'GB')
        ->set('items.1.hs_code', '4901.99')
        ->assertSee('$53.50')
        ->call('book')
        ->assertHasNoErrors();

    $shipment = Shipment::query()->sole();

    expect($shipment->export_reason)->toBe('gift')
        ->and($shipment->declared_value_cents)->toBe(5350)
        ->and($shipment->items->pluck('description')->all())->toBe(['Cotton T-shirts', 'Paperback books'])
        ->and($shipment->items[1]->hs_code)->toBe('4901.99');
});

test('a wrong HS code is refused', function () {
    bookingTo('GB')
        ->set('items.0.description', 'Phone cases')
        ->set('items.0.unit_value', '5')
        ->set('items.0.hs_code', 'abc')
        ->call('book')
        ->assertHasErrors('items.0.hs_code');
});

test('the commercial invoice lists the goods and is private to the sender and staff', function () {
    $shipment = Shipment::factory()->create(['sender_country' => 'US', 'receiver_country' => 'GB', 'export_reason' => 'sale']);
    ShipmentItem::factory()->for($shipment)->create(['description' => 'Leather wallet', 'quantity' => 2, 'unit_value_cents' => 4500]);

    $this->get(CommercialInvoice::signedUrl($shipment))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->get(route('commercial-invoice', $shipment->tracking_number))->assertForbidden();
    $this->actingAs(User::factory()->create())->get(route('account.shipments.invoice', $shipment))->assertForbidden();
    $this->actingAs(User::factory()->create(['is_admin' => true]))->get(route('admin.shipments.invoice', $shipment))->assertOk();
});

test('the invoice page renders the item lines', function () {
    $shipment = Shipment::factory()->create(['sender_country' => 'US', 'receiver_country' => 'GB', 'export_reason' => 'sale']);
    ShipmentItem::factory()->for($shipment)->create(['description' => 'Leather wallet', 'quantity' => 2, 'unit_value_cents' => 4500]);

    $html = view('pdf.commercial-invoice', ['shipment' => $shipment->load('items'), 'site' => Setting::values()])->render();

    expect($html)->toContain('Leather wallet')->toContain('$90.00')->toContain('Sale (sold to the receiver)');
});

test('staff can enter customs lines when creating an international shipment', function () {
    Livewire::actingAs(User::factory()->create(['is_admin' => true]))->test('pages::admin.shipments.form')
        ->set('sender_name', 'Megan Carter')
        ->set('sender_phone', '+1 (212) 555-0100')
        ->set('sender_address', '120 W 34th St')
        ->set('sender_city', 'New York')
        ->set('receiver_name', 'Tom Hardy')
        ->set('receiver_phone', '+44 7700 900123')
        ->set('receiver_address', '3 High Street')
        ->set('receiver_city', 'London')
        ->set('contents', 'Clothing')
        ->set('weight_lb', '4')
        ->set('price', '50')
        ->set('items.0.description', 'Silk scarf')
        ->set('items.0.unit_value', '30')
        ->call('save')
        ->assertHasNoErrors();

    expect(Shipment::query()->sole()->items()->sole()->description)->toBe('Silk scarf');
});
