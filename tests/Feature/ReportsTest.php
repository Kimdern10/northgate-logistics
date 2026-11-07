<?php

use App\Enums\ServiceLevel;
use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 11, 20)->setTime(12, 0));
});

function reportShipments(): void
{
    // Booked 5 days ago, delivered on time, paid.
    $onTime = Shipment::factory()->status(ShipmentStatus::Delivered)->create([
        'service_level' => ServiceLevel::Express, 'source' => 'website', 'price_cents' => 10000, 'weight_lb' => 4,
        'sender_country' => 'US', 'receiver_country' => 'GB',
        'created_at' => now()->subDays(5), 'estimated_delivery_date' => now()->subDays(1), 'delivered_at' => now()->subDays(2),
    ]);
    $onTime->markPaid('card', 'pi_1');

    // Booked 3 days ago, delivered late.
    Shipment::factory()->status(ShipmentStatus::Delivered)->create([
        'service_level' => ServiceLevel::Standard, 'price_cents' => 5000, 'weight_lb' => 10,
        'sender_country' => 'US', 'receiver_country' => 'GB', 'tracking_number' => 'NT5000000001',
        'created_at' => now()->subDays(3), 'estimated_delivery_date' => now()->subDays(2), 'delivered_at' => now()->subDay(),
    ]);

    // Still in transit past its date.
    Shipment::factory()->status(ShipmentStatus::InTransit)->create([
        'service_level' => ServiceLevel::Standard, 'price_cents' => 3000,
        'sender_country' => 'US', 'receiver_country' => 'CA', 'tracking_number' => 'NT5000000002',
        'created_at' => now()->subDays(10), 'estimated_delivery_date' => now()->subDays(4),
    ]);

    // Booked long ago: outside the last 30 days.
    Shipment::factory()->create(['price_cents' => 99900, 'created_at' => now()->subDays(60), 'tracking_number' => 'NT5000000003']);
}

test('the report counts bookings, money and on-time deliveries for the period', function () {
    reportShipments();

    $component = Livewire::actingAs(User::factory()->admin()->create())->test('pages::admin.reports');
    $figures = $component->instance()->figures;

    expect($figures['bookings'])->toBe(3)
        ->and($figures['online'])->toBe(1)
        ->and($figures['booked_value'])->toBe(18000)
        ->and($figures['received'])->toBe(10000)
        ->and($figures['delivered'])->toBe(2)
        ->and($figures['on_time_percent'])->toBe(50);

    $component
        ->assertSee('United States → United Kingdom')
        ->assertSee('NT5000000001')
        ->assertSee('NT5000000002')
        ->assertSee('$180.00');
});

test('changing the period changes the numbers', function () {
    reportShipments();

    $component = Livewire::actingAs(User::factory()->admin()->create())->test('pages::admin.reports')->set('period', '7');

    expect($component->instance()->figures['bookings'])->toBe(2);
});

test('the shipments of the period can be downloaded as a spreadsheet', function () {
    reportShipments();

    Livewire::actingAs(User::factory()->admin()->create())->test('pages::admin.reports')
        ->call('export')
        ->assertFileDownloaded('northgate-shipments-2026-10-22-to-2026-11-20.csv');
});

test('the CSV has one row per booking with the key columns', function () {
    reportShipments();
    $component = Livewire::actingAs(User::factory()->admin()->create())->test('pages::admin.reports');

    ob_start();
    $component->instance()->export()->sendContent();
    $csv = (string) ob_get_clean();
    $rows = array_map('str_getcsv', array_filter(explode("\n", trim($csv))));

    expect($rows)->toHaveCount(4)
        ->and($rows[0][0])->toBe('Tracking number')
        ->and(array_column($rows, 0))->toContain('NT5000000001')
        ->and(array_column($rows, 0))->not->toContain('NT5000000003');
});

test('only staff can see reports', function () {
    $this->actingAs(User::factory()->create())->get(route('admin.reports'))->assertForbidden();
    $this->actingAs(User::factory()->admin()->create())->get(route('admin.reports'))->assertOk();
});
