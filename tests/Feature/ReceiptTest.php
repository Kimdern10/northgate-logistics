<?php

use App\Enums\PaymentStatus;
use App\Models\Setting;
use App\Models\Shipment;
use App\Models\User;
use App\Support\Receipt;
use Livewire\Livewire;

test('the receipt shows the price breakdown and whether it is paid', function () {
    $shipment = Shipment::factory()->create([
        'price_cents' => 4428, 'base_fee_cents' => 2000, 'weight_charge_cents' => 2100, 'fuel_surcharge_cents' => 328, 'chargeable_weight_lb' => 3,
    ]);

    $render = fn (): string => view('pdf.receipt', ['shipment' => $shipment->fresh(), 'site' => Setting::values(), 'number' => Receipt::number($shipment)])->render();

    expect($render())->toContain('INVOICE')->toContain('AMOUNT DUE')->toContain('3 lb billed')->toContain('$21.00')->toContain('$44.28');

    $shipment->markPaid('cash', 'C-1001');

    expect($render())->toContain('RECEIPT')->toContain('PAID')->toContain('by cash at pickup')->toContain('C-1001');
});

test('staff can record a payment taken by the courier', function () {
    $shipment = Shipment::factory()->create();

    Livewire::actingAs(User::factory()->create(['is_admin' => true]))
        ->test('pages::admin.shipments.show', ['shipment' => $shipment])
        ->set('paymentMethod', 'transfer')
        ->set('paymentReference', 'TRX-55')
        ->call('markPaid')
        ->assertHasNoErrors();

    $shipment->refresh();
    expect($shipment->payment_status)->toBe(PaymentStatus::Paid)
        ->and($shipment->payment_method)->toBe('transfer')
        ->and($shipment->payment_reference)->toBe('TRX-55')
        ->and($shipment->paid_at)->not->toBeNull();
});

test('the receipt PDF is private to the sender and staff', function () {
    $sender = User::factory()->create();
    $shipment = Shipment::factory()->create(['user_id' => $sender->id]);

    $this->get(Receipt::signedUrl($shipment))->assertOk()->assertHeader('content-type', 'application/pdf');
    $this->get(route('receipt', $shipment->tracking_number))->assertForbidden();
    $this->actingAs($sender)->get(route('account.shipments.receipt', $shipment))->assertOk();
    $this->actingAs(User::factory()->create())->get(route('account.shipments.receipt', $shipment))->assertForbidden();
});
