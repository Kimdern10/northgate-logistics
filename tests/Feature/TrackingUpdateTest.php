<?php

use App\Actions\RecordTrackingUpdate;
use App\Enums\ShipmentStatus;
use App\Mail\ShipmentStatusUpdated;
use App\Models\Shipment;
use Illuminate\Support\Facades\Mail;

beforeEach(fn () => Mail::fake());

test('a new status emails the sender and the receiver', function () {
    $shipment = Shipment::factory()->create(['sender_email' => 'sender@example.com', 'receiver_email' => 'receiver@example.com']);

    $action = app(RecordTrackingUpdate::class);
    $action->handle($shipment, ShipmentStatus::PickedUp, 'Lagos, Nigeria');

    expect($shipment->fresh()->status)->toBe(ShipmentStatus::PickedUp)
        ->and($action->emailed)->toBe(2);

    Mail::assertSent(ShipmentStatusUpdated::class, fn ($mail) => $mail->hasTo('sender@example.com'));
    Mail::assertSent(ShipmentStatusUpdated::class, fn ($mail) => $mail->hasTo('receiver@example.com'));
});

test('the same address is emailed once and a missing one is skipped', function () {
    $shipment = Shipment::factory()->create(['sender_email' => 'same@example.com', 'receiver_email' => 'SAME@example.com']);

    app(RecordTrackingUpdate::class)->handle($shipment, ShipmentStatus::PickedUp, 'Lagos');

    Mail::assertSentCount(1);
});

test('no email is sent when the status stays the same or emails are switched off', function () {
    $shipment = Shipment::factory()->status(ShipmentStatus::InTransit)->create();

    app(RecordTrackingUpdate::class)->handle($shipment, ShipmentStatus::InTransit, 'Over the Sahara');
    app(RecordTrackingUpdate::class)->handle($shipment, ShipmentStatus::AtHub, 'London', notify: false);

    expect($shipment->fresh()->status)->toBe(ShipmentStatus::AtHub)
        ->and($shipment->events()->count())->toBe(2);
    Mail::assertNothingSent();
});

test('an update for an earlier time joins the timeline without changing the status', function () {
    $shipment = Shipment::factory()->status(ShipmentStatus::AtHub)->create();
    $shipment->events()->create(['status' => ShipmentStatus::AtHub, 'location' => 'London', 'happened_at' => now()]);

    app(RecordTrackingUpdate::class)->handle($shipment, ShipmentStatus::InTransit, 'Lagos', happenedAt: now()->subDay());

    expect($shipment->fresh()->status)->toBe(ShipmentStatus::AtHub)
        ->and($shipment->events()->count())->toBe(2);
    Mail::assertNothingSent();
});

test('delivery records when and who received it', function () {
    $shipment = Shipment::factory()->status(ShipmentStatus::OutForDelivery)->create();

    app(RecordTrackingUpdate::class)->handle($shipment, ShipmentStatus::Delivered, 'London', receivedBy: 'J. Smith');

    $shipment->refresh();
    expect($shipment->delivered_at)->not->toBeNull()
        ->and($shipment->received_by)->toBe('J. Smith');
});

test('a mail server failure does not lose the update', function () {
    Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP down'));
    $shipment = Shipment::factory()->create();

    $action = app(RecordTrackingUpdate::class);
    $action->handle($shipment, ShipmentStatus::PickedUp, 'Lagos');

    expect($shipment->fresh()->status)->toBe(ShipmentStatus::PickedUp)
        ->and($action->emailed)->toBe(0);
});
