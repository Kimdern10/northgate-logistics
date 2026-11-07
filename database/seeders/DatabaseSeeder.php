<?php

namespace Database\Seeders;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * On the live server this only adds the website content and rates; make the
     * owner's login there with `php artisan app:create-admin`.
     */
    public function run(): void
    {
        $this->call(ContentSeeder::class);

        if (app()->isProduction()) {
            return;
        }

        User::factory()->create([
            'name' => 'Northgate Admin',
            'email' => 'admin@northgate.example',
            'is_admin' => true,
        ]);

        $this->call(DemoShipmentSeeder::class);

        // A courier with today's pickups and deliveries, for trying the driver page.
        $driver = User::factory()->driver()->create([
            'name' => 'Carlos Diaz',
            'email' => 'driver@northgate.example',
        ]);

        Shipment::query()
            ->whereIn('status', [ShipmentStatus::Booked, ShipmentStatus::OutForDelivery])
            ->update(['driver_id' => $driver->id]);

        // Delivered parcels have been paid, half online and half to the courier.
        Shipment::query()->where('status', ShipmentStatus::Delivered)->get()->each(function (Shipment $shipment, int $index): void {
            $shipment->markPaid($index % 2 === 0 ? 'card' : 'cash', $index % 2 === 0 ? 'pi_demo_'.$shipment->id : null);
            $shipment->forceFill(['paid_at' => $shipment->created_at->addDay()])->save();
        });

        // The demo parcel to London has customs lines, so its commercial invoice can be tried.
        Shipment::query()->where('tracking_number', 'NT1000000001')->first()?->forceFill(['export_reason' => 'sample'])->replaceCustomsItems([
            ['description' => 'Cotton dresses (samples)', 'quantity' => 3, 'unit_value' => 45, 'origin_country' => 'US', 'hs_code' => '6204.42'],
            ['description' => 'Silk scarves', 'quantity' => 4, 'unit_value' => 18.5, 'origin_country' => 'US', 'hs_code' => ''],
        ]);

        // A customer account: sender of NT1000000001, so it sees that shipment and its own history.
        $customer = User::factory()->create([
            'name' => 'Emily Carter',
            'email' => 'emily.carter@example.com',
        ]);

        $customer->addresses()->createMany([
            ['label' => 'Office', 'name' => 'Emily Carter', 'company' => 'Carter & Co. Apparel', 'phone' => '+1 (212) 555-0100', 'email' => 'emily.carter@example.com', 'address' => '120 W 34th St, New York, NY 10001', 'city' => 'New York', 'country' => 'US', 'is_default' => true],
            ['label' => 'Brother in London', 'name' => 'Jack Carter', 'phone' => '+44 7700 900123', 'address' => '3 High Street', 'city' => 'London', 'country' => 'GB'],
        ]);

        $customer->watchedShipments()->attach(Shipment::query()->where('tracking_number', 'NT1000000003')->value('id'));
    }
}
