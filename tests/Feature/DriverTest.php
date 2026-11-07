<?php

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(fn () => Mail::fake());

test('only drivers and staff can open the driver page', function () {
    $this->actingAs(User::factory()->create())->get(route('driver'))->assertForbidden();
    $this->actingAs(User::factory()->driver()->create())->get(route('driver'))->assertOk();
    $this->actingAs(User::factory()->admin()->create())->get(route('driver'))->assertOk();
});

test('drivers land on their page after logging in', function () {
    $driver = User::factory()->driver()->create();

    $this->post(route('login.store'), ['email' => $driver->email, 'password' => 'password'])
        ->assertRedirect(route('driver', absolute: false));
});

test('a driver sees only their own pickups and deliveries', function () {
    $driver = User::factory()->driver()->create();
    Shipment::factory()->create(['driver_id' => $driver->id, 'sender_name' => 'My Pickup']);
    Shipment::factory()->status(ShipmentStatus::OutForDelivery)->create(['driver_id' => $driver->id, 'receiver_name' => 'My Delivery']);
    Shipment::factory()->create(['driver_id' => User::factory()->driver()->create()->id, 'sender_name' => 'Someone Else']);

    Livewire::actingAs($driver)->test('pages::driver')
        ->assertSee('My Pickup')
        ->assertDontSee('Someone Else')
        ->set('tab', 'deliveries')
        ->assertSee('My Delivery');
});

test('a driver marks a pickup as collected', function () {
    $driver = User::factory()->driver()->create();
    $shipment = Shipment::factory()->create(['driver_id' => $driver->id]);

    Livewire::actingAs($driver)->test('pages::driver')->call('pickedUp', $shipment->id);

    expect($shipment->fresh()->status)->toBe(ShipmentStatus::PickedUp)
        ->and($shipment->events()->first()->description)->toContain($driver->name);
});

test('a driver delivers with a name and a photo', function () {
    Storage::fake('local');
    $driver = User::factory()->driver()->create();
    $shipment = Shipment::factory()->status(ShipmentStatus::OutForDelivery)->create(['driver_id' => $driver->id]);

    Livewire::actingAs($driver)->test('pages::driver')
        ->call('openDelivered', $shipment->id)
        ->call('delivered')
        ->assertHasErrors('receivedBy')
        ->set('receivedBy', 'Laura Chen')
        ->set('deliveryPhoto', UploadedFile::fake()->image('door.jpg', 800, 600))
        ->call('delivered')
        ->assertHasNoErrors();

    $shipment->refresh();
    expect($shipment->status)->toBe(ShipmentStatus::Delivered)
        ->and($shipment->received_by)->toBe('Laura Chen')
        ->and($shipment->delivery_photo_path)->not->toBeNull();
});

test('a failed delivery puts the parcel on hold with the reason', function () {
    $driver = User::factory()->driver()->create();
    $shipment = Shipment::factory()->status(ShipmentStatus::OutForDelivery)->create(['driver_id' => $driver->id]);

    Livewire::actingAs($driver)->test('pages::driver')
        ->call('openFailed', $shipment->id)
        ->call('failed', 'nobody');

    expect($shipment->fresh()->status)->toBe(ShipmentStatus::OnHold)
        ->and($shipment->events()->first()->description)->toContain('Nobody home');
});

test('scanning a waybill finds the parcel and taking it out for delivery assigns it to the driver', function () {
    $driver = User::factory()->driver()->create();
    $shipment = Shipment::factory()->status(ShipmentStatus::AtHub)->create(['tracking_number' => 'NT4827301956']);

    Livewire::actingAs($driver)->test('pages::driver')
        ->set('search', 'NT4827301956')
        ->assertSee('Take it out for delivery')
        ->call('outForDelivery', $shipment->id);

    $shipment->refresh();
    expect($shipment->status)->toBe(ShipmentStatus::OutForDelivery)
        ->and($shipment->driver_id)->toBe($driver->id);
});

test('a driver cannot act on a parcel assigned to someone else', function () {
    $driver = User::factory()->driver()->create();
    $shipment = Shipment::factory()->create(['driver_id' => User::factory()->driver()->create()->id]);

    Livewire::actingAs($driver)->test('pages::driver')->call('pickedUp', $shipment->id)->assertForbidden();

    expect($shipment->fresh()->status)->toBe(ShipmentStatus::Booked);
});

test('staff can add drivers and assign them to shipments', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)->test('pages::admin.drivers')
        ->call('create')
        ->set('name', 'Carlos Diaz')
        ->set('email', 'carlos@northgate.example')
        ->set('phone', '+1 (305) 555-0101')
        ->set('password', 'courier-pass-123')
        ->call('save')
        ->assertHasNoErrors();

    $driver = User::query()->where('email', 'carlos@northgate.example')->sole();
    expect($driver->is_driver)->toBeTrue()->and($driver->hasVerifiedEmail())->toBeTrue();

    $shipment = Shipment::factory()->create();
    Livewire::actingAs($admin)->test('pages::admin.shipments.show', ['shipment' => $shipment])
        ->set('driverId', (string) $driver->id);

    expect($shipment->fresh()->driver_id)->toBe($driver->id);
});
