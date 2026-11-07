<?php

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
    Storage::fake('local');
});

function deliveredWithPhoto(): Shipment
{
    $shipment = Shipment::factory()->status(ShipmentStatus::OutForDelivery)->create([
        'sender_email' => 'sender@example.com',
        'receiver_email' => 'receiver@example.com',
    ]);
    $shipment->events()->create(['status' => ShipmentStatus::OutForDelivery, 'location' => 'London', 'happened_at' => now()->subHour()]);

    Livewire::actingAs(User::factory()->create(['is_admin' => true]))
        ->test('pages::admin.shipments.show', ['shipment' => $shipment])
        ->assertSet('status', 'delivered')
        ->set('received_by', 'J. Smith')
        ->set('deliveryPhoto', UploadedFile::fake()->image('door.jpg', 2400, 1800))
        ->call('addUpdate')
        ->assertHasNoErrors();

    return $shipment->fresh();
}

test('staff can attach a delivery photo, saved privately as a smaller WebP', function () {
    $shipment = deliveredWithPhoto();

    expect($shipment->status)->toBe(ShipmentStatus::Delivered)
        ->and($shipment->received_by)->toBe('J. Smith')
        ->and($shipment->delivery_photo_path)->toEndWith('.webp');

    Storage::disk('local')->assertExists($shipment->delivery_photo_path);
    expect(getimagesizefromstring(Storage::disk('local')->get($shipment->delivery_photo_path))[0])->toBe(1280);
});

test('the sender and receiver can see the photo, others cannot', function () {
    $shipment = deliveredWithPhoto();
    $url = route('shipments.proof', $shipment->tracking_number);

    $this->actingAs(User::factory()->create(['email' => 'sender@example.com']))->get($url)->assertOk();
    $this->actingAs(User::factory()->create(['email' => 'receiver@example.com']))->get($url)->assertOk();

    $follower = User::factory()->create();
    $follower->watchedShipments()->attach($shipment);
    $this->actingAs($follower)->get($url)->assertForbidden();
});

test('guests are sent to log in and the public page only says a photo is on file', function () {
    $shipment = deliveredWithPhoto();
    auth()->forgetGuards();

    $this->get(route('shipments.proof', $shipment->tracking_number))->assertRedirect(route('login'));
    $this->get(route('track.show', $shipment->tracking_number))
        ->assertSee('Photo on file')
        ->assertDontSee(route('shipments.proof', $shipment->tracking_number), false);
});

test('a file that is not an image is refused', function () {
    $shipment = Shipment::factory()->status(ShipmentStatus::OutForDelivery)->create();
    $shipment->events()->create(['status' => ShipmentStatus::OutForDelivery, 'location' => 'London', 'happened_at' => now()->subHour()]);

    Livewire::actingAs(User::factory()->create(['is_admin' => true]))
        ->test('pages::admin.shipments.show', ['shipment' => $shipment])
        ->set('deliveryPhoto', UploadedFile::fake()->create('notes.pdf', 50, 'application/pdf'))
        ->call('addUpdate')
        ->assertHasErrors('deliveryPhoto');

    expect($shipment->fresh()->status)->toBe(ShipmentStatus::OutForDelivery);
});
