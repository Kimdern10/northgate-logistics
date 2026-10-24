<?php

use App\Actions\RecordTrackingUpdate;
use App\Enums\ServiceLevel;
use App\Enums\ShipmentStatus;
use App\Enums\Zone;
use App\Mail\ShipmentStatusUpdated;
use App\Models\Address;
use App\Models\Rate;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

function customer(array $attributes = []): User
{
    return User::factory()->create(['email' => 'ada@example.com', ...$attributes]);
}

test('customers can sign up and land on their account', function () {
    $this->post(route('register.store'), [
        'name' => 'Ada Obi',
        'email' => 'ada@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    expect(User::query()->sole()->is_admin)->toBeFalse();
});

test('my shipments lists what I booked, sent and receive, and nothing else', function () {
    $user = customer();
    $booked = Shipment::factory()->create(['user_id' => $user->id, 'sender_email' => 'other@example.com']);
    $sentByEmail = Shipment::factory()->create(['sender_email' => 'ADA@example.com']);
    $receiving = Shipment::factory()->create(['receiver_email' => 'ada@example.com']);
    $someoneElse = Shipment::factory()->create();

    expect($user->visibleShipments()->pluck('id')->sort()->values()->all())->toBe([$booked->id, $sentByEmail->id, $receiving->id])
        ->and($user->visibleShipments('sending')->pluck('id')->sort()->values()->all())->toBe([$booked->id, $sentByEmail->id])
        ->and($user->visibleShipments('receiving')->pluck('id')->all())->toBe([$receiving->id]);

    Livewire::actingAs($user)->test('pages::account.shipments')
        ->assertSee($booked->tracking_number)
        ->assertSee($receiving->tracking_number)
        ->assertDontSee($someoneElse->tracking_number);
});

test('shipments matched by email stay hidden until the email is verified', function () {
    $user = customer(['email_verified_at' => null]);
    Shipment::factory()->create(['receiver_email' => 'ada@example.com']);

    expect($user->visibleShipments()->count())->toBe(0);
});

test('the sender sees full details and can download the waybill', function () {
    $user = customer();
    $shipment = Shipment::factory()->create(['user_id' => $user->id, 'receiver_address' => '3 High Street']);

    $this->actingAs($user)->get(route('account.shipments.show', $shipment))->assertOk()->assertSee('3 High Street')->assertSee('Ship again');
    $this->actingAs($user)->get(route('account.shipments.waybill', $shipment))->assertOk()->assertHeader('content-type', 'application/pdf');
});

test('the receiver sees the shipment but cannot download the waybill', function () {
    $user = customer();
    $shipment = Shipment::factory()->create(['receiver_email' => 'ada@example.com']);

    $this->actingAs($user)->get(route('account.shipments.show', $shipment))->assertOk()->assertDontSee('Ship again');
    $this->actingAs($user)->get(route('account.shipments.waybill', $shipment))->assertForbidden();
});

test("other people's shipments are refused", function () {
    $shipment = Shipment::factory()->create();

    $this->actingAs(customer())->get(route('account.shipments.show', $shipment))->assertForbidden();
});

test('a customer can add a shipment by tracking number and gets its emails', function () {
    Mail::fake();
    $user = customer();
    $shipment = Shipment::factory()->create(['tracking_number' => 'NT5550001111', 'sender_email' => null, 'receiver_email' => null, 'receiver_address' => '9 Secret Road']);

    Livewire::actingAs($user)->test('pages::account.dashboard')
        ->set('trackingNumber', 'nt 555 000 1111')
        ->call('watch')
        ->assertHasNoErrors();

    // Someone who only added the number sees the progress but not the addresses.
    $this->actingAs($user)->get(route('account.shipments.show', $shipment))->assertOk()->assertDontSee('9 Secret Road');

    app(RecordTrackingUpdate::class)->handle($shipment, ShipmentStatus::PickedUp, 'Lagos');
    Mail::assertSent(ShipmentStatusUpdated::class, fn ($mail) => $mail->hasTo('ada@example.com'));
});

test('an unknown tracking number cannot be added', function () {
    Livewire::actingAs(customer())->test('pages::account.dashboard')
        ->set('trackingNumber', 'NT0000000000')
        ->call('watch')
        ->assertHasErrors('trackingNumber');
});

test('the tracking page can add the shipment to my account', function () {
    $user = customer();
    $shipment = Shipment::factory()->create();

    $this->actingAs($user)
        ->post(route('track.watch', $shipment->tracking_number))
        ->assertRedirect(route('account.shipments.show', $shipment));

    expect($user->watchedShipments()->whereKey($shipment->id)->exists())->toBeTrue();
    $this->post(route('track.watch', 'NT0000000000'))->assertNotFound();
});

test('guests are asked to log in to follow a shipment', function () {
    $shipment = Shipment::factory()->create();

    $this->get(route('track.show', $shipment->tracking_number))->assertSee('Log in to follow this shipment');
    $this->post(route('track.watch', $shipment->tracking_number))->assertRedirect(route('login'));
});

test('the address book keeps one pickup address and only my own addresses', function () {
    $user = customer();
    $old = Address::factory()->for($user)->create(['is_default' => true]);
    $someoneElses = Address::factory()->create();

    Livewire::actingAs($user)->test('pages::account.addresses')
        ->call('create')
        ->set('name', 'Emeka Obi')
        ->set('phone', '+44 7700 900123')
        ->set('address', '3 High Street')
        ->set('city', 'London')
        ->set('country', 'GB')
        ->set('is_default', true)
        ->call('save')
        ->assertHasNoErrors();

    expect($user->addresses()->count())->toBe(2)
        ->and($old->fresh()->is_default)->toBeFalse();

    Livewire::actingAs($user)->test('pages::account.addresses')->call('delete', $someoneElses->id)->assertNotFound();

    expect($someoneElses->fresh())->not->toBeNull();
});

test('logged in, the pickup form uses my address book and links the shipment to me', function () {
    Mail::fake();
    Rate::factory()->create(['service_level' => ServiceLevel::Express, 'zone' => Zone::International]);
    $user = customer();
    Address::factory()->for($user)->create(['is_default' => true, 'name' => 'Ada Office', 'city' => 'Ikeja', 'country' => 'NG']);
    $friend = Address::factory()->for($user)->create(['name' => 'Tom Hardy', 'city' => 'London', 'country' => 'GB', 'phone' => '+44 7700 900000']);

    $component = Livewire::actingAs($user)->test('pages::ship')
        ->assertSet('sender_name', 'Ada Office')
        ->assertSet('sender_city', 'Ikeja')
        ->set('receiverAddressId', (string) $friend->id)
        ->assertSet('receiver_name', 'Tom Hardy')
        ->set('contents', 'Books')
        ->set('weight_kg', '2')
        ->set('service_level', 'express')
        ->set('agree', true)
        ->call('book')
        ->assertHasNoErrors();

    $shipment = Shipment::query()->sole();
    $component->assertRedirect(route('account.shipments.show', $shipment));
    expect($shipment->user_id)->toBe($user->id);
});

test('ticking save adds the receiver to my address book', function () {
    Mail::fake();
    Rate::factory()->create(['service_level' => ServiceLevel::Express, 'zone' => Zone::International]);
    $user = customer();

    Livewire::actingAs($user)->test('pages::ship')
        ->set('sender_phone', '+234 803 000 0000')
        ->set('sender_address', '1 Marina')
        ->set('sender_city', 'Lagos')
        ->set('receiver_name', 'New Friend')
        ->set('receiver_phone', '+44 7700 900000')
        ->set('receiver_address', '2 Park Lane')
        ->set('receiver_city', 'London')
        ->set('saveReceiver', true)
        ->set('contents', 'Books')
        ->set('weight_kg', '2')
        ->set('service_level', 'express')
        ->set('agree', true)
        ->call('book')
        ->assertHasNoErrors();

    expect($user->addresses()->sole()->name)->toBe('New Friend');
});

test('ship again copies only my own earlier shipment', function () {
    $user = customer();
    $mine = Shipment::factory()->create(['user_id' => $user->id, 'receiver_name' => 'Tom Hardy', 'contents' => 'Books']);
    $notMine = Shipment::factory()->create(['receiver_name' => 'Stranger']);

    Livewire::actingAs($user)->withQueryParams(['again' => $mine->tracking_number])->test('pages::ship')
        ->assertSet('receiver_name', 'Tom Hardy')
        ->assertSet('contents', 'Books');

    Livewire::actingAs($user)->withQueryParams(['again' => $notMine->tracking_number])->test('pages::ship')
        ->assertSet('receiver_name', '');
});

test('customers cannot open the admin', function () {
    $this->actingAs(customer())->get(route('admin.shipments.index'))->assertForbidden();
});
