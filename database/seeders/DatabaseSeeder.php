<?php

namespace Database\Seeders;

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

        // A customer account: sender of NG1000000001, so it sees that shipment and its own history.
        $customer = User::factory()->create([
            'name' => 'Chiamaka Obi',
            'email' => 'chiamaka.obi@example.com',
        ]);

        $customer->addresses()->createMany([
            ['label' => 'Office', 'name' => 'Chiamaka Obi', 'phone' => '+234 803 123 4567', 'email' => 'chiamaka.obi@example.com', 'address' => '12 Allen Avenue', 'city' => 'Lagos', 'country' => 'NG', 'is_default' => true],
            ['label' => 'Brother in London', 'name' => 'Emeka Obi', 'phone' => '+44 7700 900123', 'address' => '3 High Street', 'city' => 'London', 'country' => 'GB'],
        ]);

        $customer->watchedShipments()->attach(Shipment::query()->where('tracking_number', 'NG1000000003')->value('id'));
    }
}
